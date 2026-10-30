<?php
session_start();
require_once 'conexao.php';
require_once 'notificacoes.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = filter_var($_POST['usuario_id'] ?? '', FILTER_VALIDATE_INT);
    $especialidade = trim($_POST['especialidade'] ?? '');
    $horario_id = filter_var($_POST['horario_id'] ?? '', FILTER_VALIDATE_INT);
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$usuario_id || !$especialidade || !$horario_id) {
        echo "<script>alert('Preencha todos os campos obrigatórios.'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtVerifica = $pdo->prepare("
            SELECT data_consulta, DATE_FORMAT(hora_consulta, '%H:%i') AS hora_formatada, status 
            FROM horarios_disponiveis 
            WHERE id = :id FOR UPDATE
        ");
        $stmtVerifica->execute([':id' => $horario_id]);
        $horario = $stmtVerifica->fetch();

        if (!$horario || $horario['status'] !== 'livre') {
            $pdo->rollBack();
            echo "<script>alert('Este horário acabou de ser ocupado. Escolha outro.'); window.location.href = 'admin.php#novo-agendamento-admin';</script>";
            exit;
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO agendamentos (usuario_id, horario_id, especialidade, observacoes, status) 
            VALUES (:usuario_id, :horario_id, :especialidade, :observacoes, 'confirmado')
        ");
        $stmtInsert->execute([
            ':usuario_id' => $usuario_id,
            ':horario_id' => $horario_id,
            ':especialidade' => $especialidade,
            ':observacoes' => $observacoes
        ]);

        $stmtUpdate = $pdo->prepare("UPDATE horarios_disponiveis SET status = 'ocupado' WHERE id = :id");
        $stmtUpdate->execute([':id' => $horario_id]);

        $stmtUser = $pdo->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = :id");
        $stmtUser->execute([':id' => $usuario_id]);
        $paciente = $stmtUser->fetch();

        $pdo->commit();

        if ($paciente) {
            $dataBR = date('d/m/Y', strtotime($horario['data_consulta']));
            enviarEmailConfirmacao($paciente['email'], $paciente['nome'], $especialidade, $dataBR, $horario['hora_formatada'], 'confirmado');
            enviarWhatsAppAPI($paciente['telefone'], $paciente['nome'], $especialidade, $dataBR, $horario['hora_formatada'], 'confirmado');
        }

        echo "<script>alert('Consulta agendada e confirmada com sucesso!'); window.location.href = 'admin.php#consultas';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Erro ao realizar o agendamento. Tente novamente.'); window.location.href = 'admin.php#novo-agendamento-admin';</script>";
        exit;
    }
}

header("Location: admin.php");
exit;