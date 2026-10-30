<?php
session_start();
require_once 'conexao.php';
require_once 'notificacoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefone = trim($_POST['telefone'] ?? '');
    $telefoneLimpo = preg_replace('/\D/', '', $telefone);

    if (!$telefoneLimpo) {
        echo "<script>alert('Informe um número válido.'); window.history.back();</script>";
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, nome, telefone 
        FROM usuarios 
        WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :tel
    ");
    $stmt->execute([':tel' => '%' . $telefoneLimpo . '%']);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo "<script>alert('Este número de WhatsApp não foi encontrado no sistema.'); window.location.href = 'esqueci_senha.php';</script>";
        exit;
    }

    $codigo = strval(rand(100000, 999999));
    $expiracao = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET codigo_verificacao = :codigo, codigo_expiracao = :expiracao WHERE id = :id");
    $stmtUpdate->execute([
        ':codigo' => $codigo,
        ':expiracao' => $expiracao,
        ':id' => $usuario['id']
    ]);

    $_SESSION['recupera_usuario_id'] = $usuario['id'];

    $enviouPelaFerramenta = enviarCodigoWhatsAppAPI($usuario['telefone'], $usuario['nome'], $codigo);

    if (!$enviouPelaFerramenta) {
        $_SESSION['codigo_simulacao_local'] = $codigo;
    } else {
        unset($_SESSION['codigo_simulacao_local']);
    }

    header("Location: verificar_codigo.php");
    exit;
}

header("Location: esqueci_senha.php");
exit;