<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['recupera_usuario_id']) || empty($_SESSION['token_autorizado'])) {
    header("Location: esqueci_senha.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = $_POST['senha'] ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';
    $usuarioId = $_SESSION['recupera_usuario_id'];

    if (strlen($novaSenha) < 6 || $novaSenha !== $confirmaSenha) {
        echo "<script>alert('As senhas não coincidem ou possuem menos de 6 caracteres.'); window.history.back();</script>";
        exit;
    }

    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET senha_hash = :hash, codigo_verificacao = NULL, codigo_expiracao = NULL WHERE id = :id");
    $stmtUpdate->execute([
        ':hash' => $novoHash,
        ':id' => $usuarioId
    ]);

    unset($_SESSION['recupera_usuario_id'], $_SESSION['token_autorizado']);

    echo "<script>alert('Senha redefinida com sucesso! Faça login com sua nova senha.'); window.location.href = 'login.html';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha | Dr. Thiago Salomão</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="[https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap](https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap)" rel="stylesheet">
    <link rel="stylesheet" href="[https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)">
</head>
<body class="auth-body">

    <div class="auth-container">
        <div class="auth-header">
            <a href="index.html" class="logo-link">
                <h1>Dr. Thiago Salomão</h1>
                <span>Clínica Médica & Nutrologia</span>
            </a>
            <h2>Criar Nova Senha</h2>
            <p>Insira a sua nova senha de acesso.</p>
        </div>

        <form action="redefinir_senha.php" method="POST" class="agendamento-form">
            <div class="form-group">
                <label for="senha">Nova Senha *</label>
                <input type="password" id="senha" name="senha" class="form-control" minlength="6" required placeholder="Mínimo 6 caracteres">
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirmar Nova Senha *</label>
                <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" minlength="6" required placeholder="Repita a nova senha">
            </div>

            <button type="submit" class="btn btn-primary btn-submit">Salvar Nova Senha</button>
        </form>
    </div>

</body>
</html>