<?php
session_start();
require_once 'conexao.php';
require_once 'notificacoes.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'paciente') {
    header("Location: login.html");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_perfil') {
        $telefone = trim($_POST['telefone'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $novaSenha = $_POST['nova_senha'] ?? '';
        
        if (!$email) {
            $_SESSION['erro_paciente'] = "Informe um e-mail válido.";
            header("Location: paciente.php#meu-perfil");
            exit;
        }

        if (strlen($novaSenha) > 0) {
            if (strlen($novaSenha) < 6) {
                $_SESSION['erro_paciente'] = "A nova senha deve ter pelo menos 6 caracteres.";
                header("Location: paciente.php#meu-perfil");
                exit;
            }
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET telefone = :tel, email = :email, senha_hash = :hash WHERE id = :id");
            $stmt->execute([':tel' => $telefone, ':email' => $email, ':hash' => $hash, ':id' => $usuario_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET telefone = :tel, email = :email WHERE id = :id");
            $stmt->execute([':tel' => $telefone, ':email' => $email, ':id' => $usuario_id]);
        }
        
        $_SESSION['msg_paciente'] = "Dados atualizados com sucesso!";
        header("Location: paciente.php#meu-perfil");
        exit;
    }

    if ($acao === 'agendar_consulta') {
        $especialidade = $_POST['especialidade'] ?? '';
        $data_consulta = $_POST['data_consulta'] ?? '';
        $horario_id = filter_var($_POST['horario_id'] ?? '', FILTER_VALIDATE_INT);
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($especialidade && $horario_id) {
            try {
                $pdo->beginTransaction();
                
                $stmtCheck = $pdo->prepare("SELECT status FROM horarios_disponiveis WHERE id = :id FOR UPDATE");
                $stmtCheck->execute([':id' => $horario_id]);
                $horario = $stmtCheck->fetch();
                
                if ($horario && $horario['status'] === 'livre') {
                    $stmtUpdate = $pdo->prepare("UPDATE horarios_disponiveis SET status = 'ocupado' WHERE id = :id");
                    $stmtUpdate->execute([':id' => $horario_id]);
                    
                    $stmtInsert = $pdo->prepare("INSERT INTO agendamentos (usuario_id, horario_id, especialidade, observacoes) VALUES (:uid, :hid, :esp, :obs)");
                    $stmtInsert->execute([
                        ':uid' => $usuario_id,
                        ':hid' => $horario_id,
                        ':esp' => $especialidade,
                        ':obs' => $observacoes
                    ]);
                    
                    $pdo->commit();
                    $_SESSION['msg_paciente'] = "Solicitação enviada! Nossa recepção confirmará seu agendamento em breve.";
                    
                    $stmtPac = $pdo->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = :id");
                    $stmtPac->execute([':id' => $usuario_id]);
                    $pac = $stmtPac->fetch();
                    
                    $stmtHora = $pdo->prepare("SELECT DATE_FORMAT(hora_consulta, '%H:%i') as hora FROM horarios_disponiveis WHERE id = :id");
                    $stmtHora->execute([':id' => $horario_id]);
                    $horaF = $stmtHora->fetch()['hora'];
                    $dataBR = date('d/m/Y', strtotime($data_consulta));
                    
                    enviarEmailConfirmacao($pac['email'], $pac['nome'], $especialidade, $dataBR, $horaF, 'agendado');
                    enviarWhatsAppAPI($pac['telefone'], $pac['nome'], $especialidade, $dataBR, $horaF, 'agendado');
                    
                } else {
                    $pdo->rollBack();
                    $_SESSION['erro_paciente'] = "Este horário acabou de ser reservado. Por favor, escolha outro.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['erro_paciente'] = "Erro ao processar seu agendamento. Tente novamente.";
            }
        }
        header("Location: paciente.php#meus-agendamentos");
        exit;
    }
}

header("Location: paciente.php");
exit;