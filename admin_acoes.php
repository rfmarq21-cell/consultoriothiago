<?php
session_start();
require_once 'conexao.php';
require_once 'notificacoes.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Adicionar horário avulso
    if ($acao === 'adicionar_horario') {
        $data = $_POST['data_consulta'] ?? '';
        $hora = $_POST['hora_consulta'] ?? '';

        if ($data && $hora) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO horarios_disponiveis (data_consulta, hora_consulta, status) VALUES (:data, :hora, 'livre')");
                $stmt->execute([':data' => $data, ':hora' => $hora]);
                $_SESSION['msg_admin'] = "Horário liberado com sucesso!";
            } catch (PDOException $e) {
                $_SESSION['erro_admin'] = "Erro ao liberar horário.";
            }
        }
        header("Location: admin.php#gestao-horarios");
        exit;
    }

    // Gerar horários em lote
    if ($acao === 'adicionar_horarios_lote') {
        $data = $_POST['data_lote'] ?? '';
        $horaInicio = $_POST['hora_inicio'] ?? '';
        $horaFim = $_POST['hora_fim'] ?? '';
        $intervalo = intval($_POST['intervalo'] ?? 60);

        if ($data && $horaInicio && $horaFim && $intervalo > 0) {
            $timestampAtual = strtotime("$data $horaInicio");
            $timestampFim = strtotime("$data $horaFim");

            if ($timestampAtual < $timestampFim) {
                $adicionados = 0;
                $stmt = $pdo->prepare("INSERT IGNORE INTO horarios_disponiveis (data_consulta, hora_consulta, status) VALUES (:data, :hora, 'livre')");

                while ($timestampAtual <= $timestampFim) {
                    $horaFormatada = date('H:i:s', $timestampAtual);
                    $stmt->execute([':data' => $data, ':hora' => $horaFormatada]);
                    if ($stmt->rowCount() > 0) {
                        $adicionados++;
                    }
                    $timestampAtual = strtotime("+{$intervalo} minutes", $timestampAtual);
                }
                $_SESSION['msg_admin'] = "{$adicionados} horários liberados para o dia " . date('d/m/Y', strtotime($data)) . ".";
            } else {
                $_SESSION['erro_admin'] = "O horário final deve ser maior que o inicial.";
            }
        }
        header("Location: admin.php#gestao-horarios");
        exit;
    }

    // Excluir horário livre
    if ($acao === 'excluir_horario') {
        $horario_id = filter_var($_POST['horario_id'] ?? '', FILTER_VALIDATE_INT);
        if ($horario_id) {
            $stmt = $pdo->prepare("DELETE FROM horarios_disponiveis WHERE id = :id AND status = 'livre'");
            $stmt->execute([':id' => $horario_id]);
            $_SESSION['msg_admin'] = "Horário removido.";
        }
        header("Location: admin.php#gestao-horarios");
        exit;
    }

    // Cadastro rápido de paciente
    if ($acao === 'cadastrar_paciente_rapido') {
        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $senhaPadrao = $_POST['senha_padrao'] ?? 'Mudar123';

        if (!$nome || !$telefone || !$email) {
            $_SESSION['erro_admin'] = "Preencha nome, WhatsApp e um e-mail válido.";
            header("Location: admin.php#novo-agendamento-admin");
            exit;
        }

        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            $_SESSION['erro_admin'] = "Já existe um paciente cadastrado com este e-mail.";
            header("Location: admin.php#novo-agendamento-admin");
            exit;
        }

        $senhaHash = password_hash($senhaPadrao, PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha_hash, tipo) VALUES (:nome, :email, :telefone, :senha, 'paciente')");
        $stmtInsert->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':senha' => $senhaHash
        ]);

        $_SESSION['msg_admin'] = "Paciente '{$nome}' cadastrado com sucesso!";
        header("Location: admin.php#novo-agendamento-admin");
        exit;
    }

    // Atualizar status da consulta
    if ($acao === 'atualizar_status') {
        $agendamento_id = filter_var($_POST['agendamento_id'] ?? '', FILTER_VALIDATE_INT);
        $novo_status = $_POST['novo_status'] ?? '';
        $abrir_wpp = $_POST['abrir_wpp'] ?? '0';
        $status_permitidos = ['confirmado', 'concluido', 'cancelado'];

        if ($agendamento_id && in_array($novo_status, $status_permitidos)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT 
                        a.horario_id, 
                        a.especialidade,
                        u.nome, 
                        u.email, 
                        u.telefone,
                        h.data_consulta,
                        DATE_FORMAT(h.hora_consulta, '%H:%i') AS hora_formatada
                    FROM agendamentos a
                    INNER JOIN usuarios u ON a.usuario_id = u.id
                    INNER JOIN horarios_disponiveis h ON a.horario_id = h.id
                    WHERE a.id = :id FOR UPDATE
                ");
                $stmt->execute([':id' => $agendamento_id]);
                $dados = $stmt->fetch();

                if ($dados) {
                    $stmtUpdate = $pdo->prepare("UPDATE agendamentos SET status = :status WHERE id = :id");
                    $stmtUpdate->execute([':status' => $novo_status, ':id' => $agendamento_id]);

                    if ($novo_status === 'cancelado') {
                        $pdo->prepare("UPDATE horarios_disponiveis SET status = 'livre' WHERE id = :hid")
                            ->execute([':hid' => $dados['horario_id']]);
                    }
                }

                $pdo->commit();
                $_SESSION['msg_admin'] = "Status atualizado para " . ucfirst($novo_status) . ".";

                if ($dados && in_array($novo_status, ['confirmado', 'cancelado'])) {
                    $dataBR = date('d/m/Y', strtotime($dados['data_consulta']));
                    
                    enviarEmailConfirmacao($dados['email'], $dados['nome'], $dados['especialidade'], $dataBR, $dados['hora_formatada'], $novo_status);
                    $enviouPorAPI = enviarWhatsAppAPI($dados['telefone'], $dados['nome'], $dados['especialidade'], $dataBR, $dados['hora_formatada'], $novo_status);

                    if ($abrir_wpp === '1' && !$enviouPorAPI) {
                        $linkDireto = gerarLinkConfirmacaoWhatsApp($dados['telefone'], $dados['nome'], $dados['especialidade'], $dataBR, $dados['hora_formatada'], $novo_status);
                        header("Location: " . $linkDireto);
                        exit;
                    }
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['erro_admin'] = "Erro ao atualizar status.";
            }
        }
        header("Location: admin.php#consultas");
        exit;
    }
}

header("Location: admin.php");
exit;