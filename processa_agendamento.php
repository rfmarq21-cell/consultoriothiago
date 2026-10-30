<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $especialidade = trim($_POST['especialidade'] ?? '');
    $horario_id = filter_var($_POST['horario_id'] ?? '', FILTER_VALIDATE_INT);
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$especialidade || !$horario_id) {
        echo "<script>alert('Selecione a especialidade, a data e o horário.'); window.history.back();</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtVerifica = $pdo->prepare("SELECT status FROM horarios_disponiveis WHERE id = :id FOR UPDATE");
        $stmtVerifica->execute([':id' => $horario_id]);
        $horario = $stmtVerifica->fetch();

        if (!$horario || $horario['status'] !== 'livre') {
            $pdo->rollBack();
            echo "<script>alert('Ops! Este horário acabou de ser preenchido. Escolha outro.'); window.location.href = 'agendamento.php';</script>";
            exit;
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO agendamentos (usuario_id, horario_id, especialidade, observacoes, status) 
            VALUES (:usuario_id, :horario_id, :especialidade, :observacoes, 'agendado')
        ");
        $stmtInsert->execute([
            ':usuario_id' => $usuario_id,
            ':horario_id' => $horario_id,
            ':especialidade' => $especialidade,
            ':observacoes' => $observacoes
        ]);

        $stmtUpdate = $pdo->prepare("UPDATE horarios_disponiveis SET status = 'ocupado' WHERE id = :id");
        $stmtUpdate->execute([':id' => $horario_id]);

        $pdo->commit();

        echo "<script>alert('Agendamento realizado com sucesso!'); window.location.href = 'agendamento.php#meus-agendamentos';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Erro ao processar agendamento. Tente novamente.'); window.location.href = 'agendamento.php';</script>";
        exit;
    }
}