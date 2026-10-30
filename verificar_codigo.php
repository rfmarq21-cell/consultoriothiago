<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['recupera_usuario_id'])) {
    header("Location: esqueci_senha.php");
    exit;
}

$codigoSimulado = $_SESSION['codigo_simulacao_local'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoInformado = trim($_POST['codigo'] ?? '');
    $usuarioId = $_SESSION['recupera_usuario_id'];

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id AND codigo_verificacao = :codigo AND codigo_expiracao >= NOW()");
    $stmt->execute([
        ':id' => $usuarioId,
        ':codigo' => $codigoInformado
    ]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $_SESSION['token_autorizado'] = true;
        unset($_SESSION['codigo_simulacao_local']);
        header("Location: redefinir_senha.php");
        exit;
    } else {
        echo "<script>alert('Código inválido ou expirado. Verifique os 6 dígitos.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código | Dr. Thiago Salomão</title>
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
            <h2>Digite o Código</h2>
            <p>Enviamos um código de 6 dígitos para o seu WhatsApp. Insira-o abaixo para continuar.</p>
        </div>

        <?php if ($codigoSimulado): ?>
            <div style="background-color: #E8F5E9; border-left: 4px solid #25D366; padding: 15px; margin-bottom: 25px; font-size: 13px; color: #1B5E20;">
                <strong><i class="fab fa-whatsapp"></i> Ambiente de Teste:</strong><br>
                Código gerado: <strong style="font-size: 16px; letter-spacing: 2px;"><?= htmlspecialchars($codigoSimulado) ?></strong>
            </div>
        <?php endif; ?>

        <form action="verificar_codigo.php" method="POST" class="agendamento-form">
            <div class="form-group" style="text-align: center;">
                <label for="codigo">Código de 6 Dígitos *</label>
                <input type="text" id="codigo" name="codigo" class="form-control" maxlength="6" required placeholder="000000" style="text-align: center; font-size: 24px; letter-spacing: 5px;">
            </div>

            <button type="submit" class="btn btn-primary btn-submit">Validar Código</button>
        </form>

        <div class="auth-footer">
            <p><a href="esqueci_senha.php" class="link-voltar">Não recebeu? Solicitar um novo código</a></p>
        </div>
    </div>

</body>
</html>