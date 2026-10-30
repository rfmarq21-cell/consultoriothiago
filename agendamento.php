<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}


$usuario_id =$_SESSION['usuario_id'];
$primeiro_nome = explode(' ', trim($_SESSION['usuario_nome']))[0];

$stmtDatas =$pdo->query("
    SELECT DISTINCT data_consulta 
    FROM horarios_disponiveis 
    WHERE status = 'livre' AND data_consulta >= CURDATE() 
    ORDER BY data_consulta ASC
");
$datas_disponiveis =$stmtDatas->fetchAll();

$stmtMeusAgendamentos =$pdo->prepare("
    SELECT 
        a.id, 
        a.especialidade, 
        a.observacoes, 
        a.status, 
        h.data_consulta, 
        DATE_FORMAT(h.hora_consulta, '%H:%i') AS hora_formatada
    FROM agendamentos a
    INNER JOIN horarios_disponiveis h ON a.horario_id = h.id
    WHERE a.usuario_id = :usuario_id
    ORDER BY h.data_consulta DESC, h.hora_consulta DESC
");
$stmtMeusAgendamentos->execute([':usuario_id' =>$usuario_id]);
$meus_agendamentos =$stmtMeusAgendamentos->fetchAll();

function formatarDataBR($dataISO) {
    return date('d/m/Y', strtotime($dataISO));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Agendamento | Dr. Thiago Salomão</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="[https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap](https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap)" rel="stylesheet">
    <link rel="stylesheet" href="[https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)">
</head>
<body>

    <header>
        <div class="container header-container">
            <div class="logo-wrapper">
                <a href="index.html" class="logo-link">
                    <div class="logo-text">
                        <h1>Dr. Thiago Salomão</h1>
                        <span>Clínica Médica & Nutrologia</span>
                    </div>
                </a>
            </div>

            <div class="menu-mobile-icon" id="menu-icon">
                <i class="fas fa-bars"></i>
            </div>

            <nav id="navbar">
                <ul class="nav-links">
                    <li><a href="index.html">Página Inicial</a></li>
                    <li><a href="#novo-agendamento">Novo Agendamento</a></li>
                    <li><a href="#meus-agendamentos">Meus Agendamentos</a></li>
                    <li><a href="logout.php" class="link-logout">Sair da Conta</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="novo-agendamento" class="agendamento-page">
        <div class="container">
            <div class="secao-titulo">
                <h2>Olá, <?= htmlspecialchars($primeiro_nome) ?>!</h2>
                <p>Escolha uma das datas e horários disponíveis liberados pela clínica para agendar sua consulta.</p>
            </div>

            <div class="form-container">
                <form action="processa_agendamento.php" method="POST" class="agendamento-form">
                    
                    <div class="form-group">
                        <label for="especialidade">Tipo de Atendimento *</label>
                        <select id="especialidade" name="especialidade" class="form-control" required>
                            <option value="" disabled selected>Selecione o serviço desejado</option>
                            <option value="Clínica Médica (Check-up)">Clínica Médica (Check-up)</option>
                            <option value="Nutrologia (Emagrecimento/Metabolismo)">Nutrologia (Emagrecimento/Metabolismo)</option>
                            <option value="Performance e Hipertrofia">Performance e Hipertrofia</option>
                            <option value="Retorno de Consulta">Retorno de Consulta</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data">Datas Disponíveis *</label>
                            <select id="data" name="data_consulta" class="form-control" required>
                                <option value="" disabled selected>Selecione uma data</option>
                                <?php foreach ($datas_disponiveis as$linha): ?>
                                    <option value="<?= htmlspecialchars($linha['data_consulta']) ?>">
                                        <?= formatarDataBR($linha['data_consulta']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="horario">Horários Disponíveis *</label>
                            <select id="horario" name="horario_id" class="form-control" required disabled>
                                <option value="" disabled selected>Escolha uma data primeiro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacoes">Observações (Opcional)</label>
                        <textarea id="observacoes" name="observacoes" class="form-control" rows="4" placeholder="Algum detalhe que o Dr. Thiago precise saber antes da consulta?"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit">Confirmar Agendamento</button>
                </form>
            </div>
        </div>
    </section>

    <section id="meus-agendamentos" class="meus-agendamentos-section">
        <div class="container">
            <div class="secao-titulo">
                <h2>Meus Agendamentos</h2>
                <p>Acompanhe abaixo o histórico e o status das suas consultas solicitadas.</p>
            </div>

            <div class="agendamentos-lista">
                <?php if (empty($meus_agendamentos)): ?>
                    <p style="text-align: center; color: var(--text-color);">Você ainda não possui agendamentos registrados.</p>
                <?php else: ?>
                    <?php foreach ($meus_agendamentos as$item): ?>
                        <div class="card-agendamento-item">
                            <div class="agendamento-info">
                                <h3><?= htmlspecialchars($item['especialidade']) ?></h3>
                                <p><i class="far fa-calendar-alt"></i> <?= formatarDataBR($item['data_consulta']) ?> às <?= htmlspecialchars($item['hora_formatada']) ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> The Mall Villa Bella - Sala 50, Campinas - SP</p>
                            </div>
                            <div class="agendamento-acoes" style="display: flex; align-items: center; gap: 15px;">
                                <?php if ($item['status'] === 'confirmado'): ?>
                                    <span class="status-badge status-confirmado">Confirmado</span>
                                <?php elseif ($item['status'] === 'cancelado'): ?>
                                    <span class="status-badge status-cancelado">Cancelado</span>
                                <?php else: ?>
                                    <span class="status-badge status-agendado">Aguardando Confirmação</span>
                                <?php endif; ?>

                                <?php if ($item['status'] !== 'cancelado' &&$item['status'] !== 'concluido'): ?>
                                    <form action="cancelar_horario.php" method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                        <input type="hidden" name="agendamento_id" value="<?= $item['id'] ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--error-color); cursor: pointer; font-size: 13px; text-decoration: underline;">
                                            Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container footer-content">
            <div class="footer-text">
                <p>&copy; 2026 Dr. Thiago Salomão. Todos os direitos reservados.</p>
                <p>Desenvolvido por Rafael Marques Fernandes</p>
            </div>
        </div>
    </footer>

    <script>
        const menuIcon = document.getElementById('menu-icon');
        const navbar = document.getElementById('navbar');
        const linksMenu = document.querySelectorAll('.nav-links li a');

        menuIcon.addEventListener('click', () => navbar.classList.toggle('ativo'));
        linksMenu.forEach(link => link.addEventListener('click', () => navbar.classList.remove('ativo')));

        const selectData = document.getElementById('data');
        const selectHorario = document.getElementById('horario');

        selectData.addEventListener('change', async () => {
            const dataEscolhida = selectData.value;
            selectHorario.innerHTML = '<option value="" disabled selected>Carregando horários...</option>';
            selectHorario.disabled = true;

            try {
                const resposta = await fetch(`buscar_horarios.php?data=${encodeURIComponent(dataEscolhida)}`);
                const horarios = await resposta.json();

                selectHorario.innerHTML = '';

                if (horarios.length === 0) {
                    selectHorario.innerHTML = '<option value="" disabled selected>Nenhum horário livre nesta data</option>';
                } else {
                    selectHorario.innerHTML = '<option value="" disabled selected>Selecione um horário</option>';
                    horarios.forEach(h => {
                        const option = document.createElement('option');
                        option.value = h.id;
                        option.textContent = h.hora_formatada;
                        selectHorario.appendChild(option);
                    });
                    selectHorario.disabled = false;
                }
            } catch (erro) {
                selectHorario.innerHTML = '<option value="" disabled selected>Erro ao carregar horários</option>';
            }
        });
    </script>
</body>
</html>