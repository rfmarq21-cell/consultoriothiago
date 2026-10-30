<?php
require_once 'conexao.php';

$token = $_GET['token'] ?? '';
$usuarioValido = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = :token AND token_expiracao >= NOW()");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();
    if ($usuario) {
        $usuarioValido = true;
    }
}

// Se o formulário for enviado para salvar a nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenPost = $_POST['token'] ?? '';
    $novaSenha = $_POST['senha'] ?? '';
    $confirmaSenha = $_POST['confirma_senha'] ?? '';

    if (strlen($novaSenha) < 6 || $novaSenha !== $confirmaSenha) {
        echo "<script>alert('As senhas não coincidem ou possuem menos de 6 caracteres.'); window.history.back();</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacao = :token AND token_expiracao >= NOW()");
    $stmt->execute([':token' => $tokenPost]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        // Atualiza a senha e limpa o token para que ele não possa ser reutilizado
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET senha_hash = :hash, token_recuperacao = NULL, token_expiracao = NULL WHERE id = :id");
        $stmtUpdate->execute([
            ':hash' => $novoHash,
            ':id' => $usuario['id']
        ]);

        echo "<script>alert('Senha redefinida com sucesso! Faça login com sua nova senha.'); window.location.href = 'login.html';</script>";
        exit;
    } else {
        echo "<script>alert('Link inválido ou expirado.'); window.location.href = 'esqueci_senha.html';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha | Dr. Thiago Salomão</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-body">

    <div class="auth-container">
        <div class="auth-header">
            <a href="index.html" class="logo-link">
                <h1>Dr. Thiago Salomão</h1>
                <span>Clínica Médica & Nutrologia</span>
            </a>
            <h2>Criar Nova Senha</h2>
            <p>Digite sua nova senha abaixo.</p>
        </div>

        <?php if (!$usuarioValido): ?>
            <div style="text-align: center; padding: 20px;">
                <p style="color: var(--error-color); margin-bottom: 20px;">Este link de recuperação é inválido ou já expirou.</p>
                <a href="esqueci_senha.html" class="btn btn-primary">Solicitar Novo Link</a>
            </div>
        <?php else: ?>
            <form action="redefinir_senha.php" method="POST" class="agendamento-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

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
        <?php endif; ?>
    </div>

</body>
</html>