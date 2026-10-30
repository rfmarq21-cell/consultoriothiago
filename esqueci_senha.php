<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha via WhatsApp | Dr. Thiago Salomão</title>
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
            <h2>Recuperar Senha</h2>
            <p>Digite o número do seu WhatsApp cadastrado para receber o código de verificação.</p>
        </div>

        <form action="processa_esqueci_senha.php" method="POST" class="agendamento-form">
            <div class="form-group">
                <label for="telefone">WhatsApp Cadastrado *</label>
                <input type="tel" id="telefone" name="telefone" class="form-control" maxlength="15" required placeholder="(19) 90000-0000">
            </div>

            <button type="submit" class="btn btn-primary btn-submit">Enviar Código via WhatsApp</button>
        </form>

        <div class="auth-footer">
            <p><a href="login.html" class="link-voltar"><i class="fas fa-arrow-left"></i> Voltar para o Login</a></p>
        </div>
    </div>

    <script>
        const inputTelefone = document.getElementById('telefone');
        inputTelefone.addEventListener('input', (e) => {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (v.length > 6) {
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (v.length > 0) {
                v = v.replace(/^(\d*)/, '($1');
            }
            e.target.value = v;
        });
    </script>
</body>
</html>