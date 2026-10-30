<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit;
}

$agendamento_id = filter_var($_POST['agendamento_id'] ?? '', FILTER_VALIDATE_INT);
$usuario_id = $_SESSION['usuario_id'];

if ($agendamento_id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT horario_id, status FROM agendamentos WHERE id = :id AND usuario_id = :usuario_id FOR UPDATE");
        $stmt->execute([':id' => $agendamento_id, ':usuario_id' => $usuario_id]);
        $agendamento = $stmt->fetch();

        if ($agendamento && $agendamento['status'] !== 'cancelado') {
            $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = :id")
                ->execute([':id' => $agendamento_id]);

            $pdo->prepare("UPDATE horarios_disponiveis SET status = 'livre' WHERE id = :horario_id")
                ->execute([':horario_id' => $agendamento['horario_id']]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

header("Location: agendamento.php#meus-agendamentos");
exit;