<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) && ($_SESSION['usuario_tipo'] ?? '') !== 'paciente') {
    header("Location: login.html");
    exit;
}

$usuario_id =$_SESSION['usuario_id'];

$msgSucesso =$_SESSION['msg_paciente'] ?? null;
$msgErro =$_SESSION['erro_paciente'] ?? null;
unset($_SESSION['msg_paciente'],$_SESSION['erro_paciente']);

$stmtPac =$pdo->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = :id");
$stmtPac->execute([':id' =>$usuario_id]);
$paciente =$stmtPac->fetch();

$primeiroNome = explode(' ', trim($paciente['nome']))[0];

$stmtAg =$pdo->prepare("
    SELECT 
        a.id, a.especialidade, a.status,
        h.data_consulta, 
        DATE_FORMAT(h.hora_consulta, '%H:%i') AS hora_formatada
    FROM agendamentos a
    INNER JOIN horarios_disponiveis h ON a.horario_id = h.id
    WHERE a.usuario_id = :uid
    ORDER BY h.data_consulta DESC, h.hora_consulta DESC
");
$stmtAg->execute([':uid' =>$usuario_id]);
$agendamentos =$stmtAg->fetchAll();

function formatarDataBR($dataISO) {
    return date('d/m/Y', strtotime($dataISO));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Paciente | Dr. Thiago Salomão</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alerta-feedback {
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 1100px;
            border-radius: 3px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .alerta-sucesso { background-color: #E8F5E9; color: #1B5E20; border-left: 4px solid #2E7D32; }
        .alerta-erro { background-color: #FFEBEE; color: #B71C1C; border-left: 4px solid #C62828; }
        .paciente-header-info { text-align: center; margin: 130px 0 30px; font-family: 'Playfair Display', serif; }
        .paciente-header-info h2 { color: #222; font-size: 28px; }
        .btn-wpp-suporte { background-color: #25D366; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-wpp-suporte:hover { background-color: #128C7E; }
        .admin-table-container {
            background-color: #FFFFFF;
            border: 1px solid #E0E0E0;
            border-radius: 2px;
            padding: 25px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        .admin-table th {
            background-color: #F5F5F5;
            color: #222222;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            padding: 15px;
            border-bottom: 2px solid #E0E0E0;
        }
        .admin-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #EEEEEE;
            vertical-align: middle;
            line-height: 1.5;
        }
        .admin-table tbody tr:hover {
            background-color: #FAFAFA;
        }
        .status-concluido {
            background-color: #E8EAED;
            color: #3C4043;
        }
    </style>
</head>
<body>

    <header>
        <div class="container header-container">
            <div class="logo-wrapper">
                <a href="index.html" class="logo-link">
                    <div class="logo-text">
                        <h1>Dr. Thiago Salomão</h1>
                        <span>Área do Paciente</span>
                    </div>
                </a>
            </div>

            <div class="menu-mobile-icon" id="menu-icon">
                <i class="fas fa-bars"></i>
            </div>

            <nav id="navbar">
                <ul class="nav-links">
                    <li><a href="#meus-agendamentos">Minhas Consultas</a></li>
                    <li><a href="#novo-agendamento">Agendar Nova</a></li>
                    <li><a href="#meu-perfil">Meu Perfil</a></li>
                    <li><a href="logout.php" class="link-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container paciente-header-info">
        <h2>Bem-vindo(a), <?= htmlspecialchars($primeiroNome) ?>!</h2>
        <p>Acompanhe os seus agendamentos ou reserve um novo horário.</p>
    </div>

    <?php if ($msgSucesso): ?>
        <div class="container"><div class="alerta-feedback alerta-sucesso"><span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msgSucesso) ?></span><button onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px;color:#1B5E20;">&times;</button></div></div>
    <?php endif; ?>
    <?php if ($msgErro): ?>
        <div class="container"><div class="alerta-feedback alerta-erro"><span><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($msgErro) ?></span><button onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px;color:#B71C1C;">&times;</button></div></div>
    <?php endif; ?>

    <section id="meus-agendamentos" class="agendamento-page" style="padding-top: 20px;">
        <div class="container">
            <div class="admin-table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px;">
                    <h3 style="color: #222;"><i class="far fa-calendar-alt"></i> Histórico de Consultas</h3>
                    <a href="https://wa.me/5519981514744" target="_blank" class="btn-wpp-suporte"><i class="fab fa-whatsapp"></i> Falar com a Recepção</a>
                </div>

                <?php if (empty($agendamentos)): ?>
                    <p style="text-align: center; padding: 40px; color: #666;">Você ainda não possui consultas agendadas.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Data e Hora</th>
                                <th>Especialidade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as$ag): ?>
                                <tr>
                                    <td>
                                        <strong><?= formatarDataBR($ag['data_consulta']) ?></strong><br>
                                        <span><?= htmlspecialchars($ag['hora_formatada']) ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($ag['especialidade']) ?></strong></td>
                                    <td>
                                        <?php if ($ag['status'] === 'confirmado'): ?>
                                            <span class="status-badge status-confirmado">Confirmada</span>
                                        <?php elseif ($ag['status'] === 'cancelado'): ?>
                                            <span class="status-badge status-cancelado">Cancelada</span>
                                        <?php elseif ($ag['status'] === 'concluido'): ?>
                                            <span class="status-badge status-concluido">Concluída</span>
                                        <?php else: ?>
                                            <span class="status-badge status-agendado">Aguardando Confirmação</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p style="font-size: 12px; color: #777; margin-top: 15px; text-align: center;">* Para cancelar ou reagendar uma consulta confirmada, entre em contato com a nossa recepção pelo WhatsApp.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="novo-agendamento" class="meus-agendamentos-section" style="background-color: var(--white); border-top: 1px solid #E0E0E0;">
        <div class="container">
            <div class="secao-titulo">
                <h2>Agendar Nova Consulta</h2>
                <p>Selecione o serviço, data e horário para reservar a sua próxima visita.</p>
            </div>

            <div class="form-container">
                <form action="paciente_acoes.php" method="POST" class="agendamento-form">
                    <input type="hidden" name="acao" value="agendar_consulta">
                    
                    <div class="form-group">
                        <label for="especialidade_pac">Tipo de Atendimento *</label>
                        <select id="especialidade_pac" name="especialidade" class="form-control" required>
                            <option value="" disabled selected>Selecione o serviço</option>
                            <option value="Clínica Médica (Check-up)">Clínica Médica (Check-up)</option>
                            <option value="Nutrologia (Emagrecimento/Metabolismo)">Nutrologia (Emagrecimento/Metabolismo)</option>
                            <option value="Performance e Hipertrofia">Performance e Hipertrofia</option>
                            <option value="Retorno de Consulta">Retorno de Consulta</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_paciente">Data Desejada *</label>
                            <select id="data_paciente" name="data_consulta" class="form-control" required>
                                <option value="" disabled selected>Selecione uma data</option>
                                <?php
                                $stmtDatas =$pdo->query("SELECT DISTINCT data_consulta FROM horarios_disponiveis WHERE status = 'livre' AND data_consulta >= CURDATE() ORDER BY data_consulta ASC");
                                while ($d =$stmtDatas->fetch()):
                                ?>
                                    <option value="<?= $d['data_consulta'] ?>"><?= date('d/m/Y', strtotime($d['data_consulta'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="horario_paciente">Horário *</label>
                            <select id="horario_paciente" name="horario_id" class="form-control" required disabled>
                                <option value="" disabled selected>Escolha uma data primeiro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacoes_pac">Motivo da consulta ou observação (Opcional)</label>
                        <textarea id="observacoes_pac" name="observacoes" class="form-control" rows="3" placeholder="Ex: Gostaria de apresentar exames recentes..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit" style="width: 100%;">Solicitar Agendamento</button>
                </form>
            </div>
        </div>
    </section>

    <section id="meu-perfil" class="meus-agendamentos-section" style="background-color: var(--light-bg); border-top: 1px solid #E0E0E0;">
        <div class="container">
            <div class="secao-titulo">
                <h2>Meu Perfil</h2>
                <p>Mantenha as suas informações de contato atualizadas para receber as nossas notificações.</p>
            </div>

            <div class="form-container" style="margin-bottom: 50px;">
                <form action="paciente_acoes.php" method="POST" class="agendamento-form">
                    <input type="hidden" name="acao" value="atualizar_perfil">
                    
                    <div class="form-group">
                        <label>Nome Completo (Apenas visualização)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($paciente['nome']) ?>" disabled style="background-color: #EEE;">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email_perfil">E-mail *</label>
                            <input type="email" id="email_perfil" name="email" class="form-control" value="<?= htmlspecialchars($paciente['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone_perfil">WhatsApp *</label>
                            <input type="tel" id="telefone_perfil" name="telefone" class="form-control" value="<?= htmlspecialchars($paciente['telefone']) ?>" maxlength="15" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #CCC;">
                        <label for="nova_senha">Nova Senha (Preencha apenas se desejar alterar)</label>
                        <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Mínimo de 6 caracteres">
                    </div>

                    <button type="submit" class="btn btn-submit" style="background-color: #444; color: #FFF; width: 100%; margin-top: 15px;">Atualizar Meus Dados</button>
                </form>
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
        if (menuIcon && navbar) {
            menuIcon.addEventListener('click', () => navbar.classList.toggle('ativo'));
        }

        const inputTelPerfil = document.getElementById('telefone_perfil');
        if (inputTelPerfil) {
            inputTelPerfil.addEventListener('input', (e) => {
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
        }

        const selectDataPac = document.getElementById('data_paciente');
        const selectHorarioPac = document.getElementById('horario_paciente');

        if (selectDataPac && selectHorarioPac) {
            selectDataPac.addEventListener('change', async () => {
                const dataEscolhida = selectDataPac.value;
                selectHorarioPac.innerHTML = '<option value="" disabled selected>Carregando horários...</option>';
                selectHorarioPac.disabled = true;

                try {
                    const resposta = await fetch(`buscar_horarios.php?data=${encodeURIComponent(dataEscolhida)}`);
                    const horarios = await resposta.json();

                    selectHorarioPac.innerHTML = '';

                    if (horarios.length === 0) {
                        selectHorarioPac.innerHTML = '<option value="" disabled selected>Nenhum horário disponível</option>';
                    } else {
                        selectHorarioPac.innerHTML = '<option value="" disabled selected>Selecione um horário</option>';
                        horarios.forEach(h => {
                            const option = document.createElement('option');
                            option.value = h.id;
                            option.textContent = h.hora_formatada;
                            selectHorarioPac.appendChild(option);
                        });
                        selectHorarioPac.disabled = false;
                    }
                } catch (erro) {
                    selectHorarioPac.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
                }
            });
        }
    </script>
</body>
</html>