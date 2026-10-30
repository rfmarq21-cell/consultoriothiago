<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$data = $_GET['data'] ?? '';

if (!$data) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, DATE_FORMAT(hora_consulta, '%H:%i') AS hora_formatada 
    FROM horarios_disponiveis 
    WHERE data_consulta = :data AND status = 'livre' 
    ORDER BY hora_consulta ASC
");
$stmt->execute([':data' => $data]);

echo json_encode($stmt->fetchAll());