<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (!$nome || !$email || !$telefone || strlen($senha) < 6 || $senha !== $confirma_senha) {
        echo "<script>alert('Preencha todos os campos corretamente.'); window.history.back();</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo "<script>alert('Este e-mail já está cadastrado. Faça login.'); window.location.href = 'login.html';</script>";
        exit;
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha_hash) VALUES (:nome, :email, :telefone, :senha_hash)");
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':telefone' => $telefone,
        ':senha_hash' => $senha_hash
    ]);

    echo "<script>alert('Cadastro realizado com sucesso! Faça seu login.'); window.location.href = 'login.html';</script>";
    exit;
}