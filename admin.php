<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
    header("Location: login.html");
    exit;
}

$msgSucesso = $_SESSION['msg_admin'] ?? null;
$msgErro    = $_SESSION['erro_admin'] ?? null;
unset($_SESSION['msg_admin'], $_SESSION['erro_admin']);

$stmtAgendamentos = $pdo->query("
    SELECT 
        a.id AS agendamento_id,
        a.especialidade,
        a.observacoes,
        a.status,
        u.nome AS paciente_nome,
        u.email AS paciente_email,
        u.telefone AS paciente_telefone,
        h.data_consulta,
        DATE_FORMAT(h.hora_consulta, '%H:%i') AS hora_formatada
    FROM agendamentos a
    INNER JOIN usuarios u ON a.usuario_id = u.id
    INNER JOIN horarios_disponiveis h ON a.horario_id = h.id
    ORDER BY h.data_consulta ASC, h.hora_consulta ASC
");
$agendamentos = $stmtAgendamentos->fetchAll();

$stmtHorarios = $pdo->query("
    SELECT id, data_consulta, DATE_FORMAT(hora_consulta, '%H:%i') AS hora_formatada, status
    FROM horarios_disponiveis
    WHERE data_consulta >= CURDATE()
    ORDER BY data_consulta ASC, hora_consulta ASC
");
$horarios = $stmtHorarios->fetchAll();

function formatarDataBR($dataISO) {
    return date('d/m/Y', strtotime($dataISO));
}

function criarLinkWhatsApp($telefone, $nome, $dataBR, $hora, $especialidade) {
    $numLimpo = preg_replace('/\D/', '', $telefone);
    if (strlen($numLimpo) <= 11) {
        $numLimpo = '55' . $numLimpo;
    }
    $primeiroNome = explode(' ', trim($nome))[0];
    $msg = "Olá, {$primeiroNome}! Tudo bem? Passando para confirmar a sua consulta de *{$especialidade}* com o Dr. Thiago Salomão no dia *{$dataBR} às {$hora}* (The Mall Villa Bella - Sala 50, Campinas). Podemos confirmar?";
    return "https://wa.me/{$numLimpo}?text=" . urlencode($msg);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Dr. Thiago Salomão</title>
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
        .alerta-sucesso {
            background-color: #E8F5E9;
            color: #1B5E20;
            border-left: 4px solid #2E7D32;
        }
        .alerta-erro {
            background-color: #FFEBEE;
            color: #B71C1C;
            border-left: 4px solid #C62828;
        }
        .grid-gerador-lote {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filtro-consultas {
            background: #FAFAFA;
            border: 1px solid #E0E0E0;
            border-left: 4px solid #222;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-radius: 4px;
        }
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
        .obs-texto {
            font-size: 12px;
            color: #777777;
            font-style: italic;
            margin-top: 5px;
        }
        .status-concluido {
            background-color: #E8EAED;
            color: #3C4043;
        }
        .admin-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .admin-actions form {
            margin: 0;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            font-size: 12px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            border-radius: 3px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s, transform 0.2s;
            color: #FFFFFF;
        }
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-wpp {
            background-color: #25D366;
        }
        .btn-confirm {
            background-color: #1E8E3E;
        }
        .btn-cancel {
            background-color: #D93025;
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
                        <span>Painel Administrativo</span>
                    </div>
                </a>
            </div>

            <div class="menu-mobile-icon" id="menu-icon">
                <i class="fas fa-bars"></i>
            </div>

            <nav id="navbar">
                <ul class="nav-links">
                    <li><a href="#consultas">Consultas Agendadas</a></li>
                    <li><a href="#novo-agendamento-admin">Recepção / Agendar</a></li>
                    <li><a href="#gestao-horarios">Liberar Horários</a></li>
                    <li><a href="index.html" target="_blank">Ver Site</a></li>
                    <li><a href="logout.php" class="link-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <?php if ($msgSucesso): ?>
        <div class="container" style="margin-top: 110px; margin-bottom: -100px;">
            <div class="alerta-feedback alerta-sucesso">
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msgSucesso) ?></span>
                <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px;color:#1B5E20;">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msgErro): ?>
        <div class="container" style="margin-top: 110px; margin-bottom: -100px;">
            <div class="alerta-feedback alerta-erro">
                <span><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($msgErro) ?></span>
                <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px;color:#B71C1C;">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <section id="consultas" class="agendamento-page">
        <div class="container">
            <div class="secao-titulo">
                <h2>Agenda de Consultas</h2>
                <p>Gerencie as solicitações de agendamento, confirme presenças pelo WhatsApp e acompanhe os pacientes.</p>
            </div>

            <div class="filtro-consultas">
                <label for="filtro_data_tabela" style="font-weight: 600; font-size: 14px; margin-bottom: 0;"><i class="far fa-calendar-alt"></i> Filtrar por Data do Atendimento:</label>
                <input type="date" id="filtro_data_tabela" class="form-control" style="width: auto; padding: 8px 12px; margin-bottom: 0;">
                <button type="button" id="btn_limpar_filtro" class="btn" style="background-color: #666; color: #FFF; padding: 10px 15px; font-size: 12px; cursor: pointer;">
                    <i class="fas fa-times"></i> Limpar Filtro
                </button>
            </div>

            <div class="admin-table-container">
                <?php if (empty($agendamentos)): ?>
                    <p style="text-align: center; padding: 30px;">Nenhuma consulta agendada até o momento.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Data / Hora</th>
                                <th>Paciente</th>
                                <th>Atendimento & Obs</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $ag): 
                                $dataBR = formatarDataBR($ag['data_consulta']);
                                $linkWpp = criarLinkWhatsApp($ag['paciente_telefone'], $ag['paciente_nome'], $dataBR, $ag['hora_formatada'], $ag['especialidade']);
                            ?>
                                <tr class="linha-agendamento" data-data="<?= $ag['data_consulta'] ?>">
                                    <td>
                                        <strong><?= $dataBR ?></strong><br>
                                        <span><?= htmlspecialchars($ag['hora_formatada']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ag['paciente_nome']) ?></strong><br>
                                        <small><?= htmlspecialchars($ag['paciente_telefone']) ?></small><br>
                                        <small><?= htmlspecialchars($ag['paciente_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ag['especialidade']) ?></strong>
                                        <?php if (!empty($ag['observacoes'])): ?>
                                            <p class="obs-texto">"<?= htmlspecialchars($ag['observacoes']) ?>"</p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ag['status'] === 'confirmado'): ?>
                                            <span class="status-badge status-confirmado">Confirmado</span>
                                        <?php elseif ($ag['status'] === 'cancelado'): ?>
                                            <span class="status-badge status-cancelado">Cancelado</span>
                                        <?php elseif ($ag['status'] === 'concluido'): ?>
                                            <span class="status-badge status-concluido">Concluído</span>
                                        <?php else: ?>
                                            <span class="status-badge status-agendado">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="admin-actions">
                                            <a href="<?= $linkWpp ?>" target="_blank" class="btn-action btn-wpp" title="Conversar via WhatsApp">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>

                                            <?php if ($ag['status'] !== 'confirmado' && $ag['status'] !== 'cancelado'): ?>
                                                <form action="admin_acoes.php" method="POST" target="_blank" onsubmit="setTimeout(() => window.location.reload(), 800);">
                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                    <input type="hidden" name="agendamento_id" value="<?= $ag['agendamento_id'] ?>">
                                                    <input type="hidden" name="novo_status" value="confirmado">
                                                    <input type="hidden" name="abrir_wpp" value="1">
                                                    <button type="submit" class="btn-action btn-confirm" title="Confirmar no sistema e notificar via WhatsApp">
                                                        <i class="fab fa-whatsapp"></i> Confirmar + Wpp
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($ag['status'] !== 'cancelado'): ?>
                                                <form action="admin_acoes.php" method="POST" target="_blank" onsubmit="setTimeout(() => window.location.reload(), 800);">
                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                    <input type="hidden" name="agendamento_id" value="<?= $ag['agendamento_id'] ?>">
                                                    <input type="hidden" name="novo_status" value="cancelado">
                                                    <input type="hidden" name="abrir_wpp" value="1">
                                                    <button type="submit" class="btn-action btn-cancel" title="Cancelar consulta e avisar paciente">
                                                        <i class="fab fa-whatsapp"></i> Cancelar + Wpp
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="novo-agendamento-admin" class="meus-agendamentos-section" style="background-color: var(--white); border-top: 1px solid #E0E0E0;">
        <div class="container">
            <div class="secao-titulo">
                <h2>Agendar Consulta (Recepção)</h2>
                <p>Busque um paciente cadastrado ou cadastre um novo paciente rapidamente pelo telefone.</p>
            </div>

            <div class="form-container">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                    <button type="button" onclick="toggleCadastroRapido()" class="btn" style="background-color: #222; color: #FFF; padding: 10px 18px; font-size: 12px; cursor: pointer;">
                        <i class="fas fa-user-plus"></i> + Cadastrar Novo Paciente
                    </button>
                </div>

                <div id="box-cadastro-rapido" style="display: none; background-color: #FAFAFA; border: 1px solid #E0E0E0; border-left: 4px solid #E65C00; padding: 25px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px; color: #222;"><i class="fas fa-user-plus"></i> Novo Paciente (Cadastro Rápido)</h3>
                    <form action="admin_acoes.php" method="POST">
                        <input type="hidden" name="acao" value="cadastrar_paciente_rapido">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="novo_nome">Nome Completo *</label>
                                <input type="text" id="novo_nome" name="nome" class="form-control" required placeholder="Ex: Maria da Silva">
                            </div>
                            <div class="form-group">
                                <label for="novo_telefone">WhatsApp *</label>
                                <input type="tel" id="novo_telefone" name="telefone" class="form-control" maxlength="15" required placeholder="(19) 90000-0000">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="novo_email">E-mail do Paciente *</label>
                                <input type="email" id="novo_email" name="email" class="form-control" required placeholder="paciente@email.com">
                            </div>
                            <div class="form-group">
                                <label for="senha_padrao">Senha Inicial de Acesso</label>
                                <input type="text" id="senha_padrao" name="senha_padrao" class="form-control" value="Clinica123" required>
                                <small style="color: #666; font-size: 11px;">O paciente poderá alterar depois.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Salvar Paciente e Atualizar Lista</button>
                    </form>
                </div>

                <form action="admin_agenda_paciente.php" method="POST" class="agendamento-form">
                    <div class="form-group">
                        <label for="filtro_paciente">Pesquisar Paciente (Nome ou Telefone)</label>
                        <input type="text" id="filtro_paciente" class="form-control" placeholder="Digite para filtrar a lista abaixo...">
                    </div>

                    <div class="form-group">
                        <label for="busca_paciente">Selecione o Paciente *</label>
                        <select id="busca_paciente" name="usuario_id" class="form-control" size="4" required style="height: auto; min-height: 120px;">
                            <option value="" disabled selected>--- Digite acima ou escolha da lista ---</option>
                            <?php
                            $stmtPacientes = $pdo->query("SELECT id, nome, email, telefone FROM usuarios WHERE tipo = 'paciente' ORDER BY nome ASC");
                            while ($pac = $stmtPacientes->fetch()):
                            ?>
                                <option value="<?= $pac['id'] ?>" data-nome="<?= strtolower($pac['nome']) ?>" data-tel="<?= $pac['telefone'] ?>">
                                    <?= htmlspecialchars($pac['nome']) ?> — Tel: <?= htmlspecialchars($pac['telefone']) ?> (<?= htmlspecialchars($pac['email']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="especialidade_admin">Tipo de Atendimento *</label>
                        <select id="especialidade_admin" name="especialidade" class="form-control" required>
                            <option value="" disabled selected>Selecione o serviço desejado</option>
                            <option value="Clínica Médica (Check-up)">Clínica Médica (Check-up)</option>
                            <option value="Nutrologia (Emagrecimento/Metabolismo)">Nutrologia (Emagrecimento/Metabolismo)</option>
                            <option value="Performance e Hipertrofia">Performance e Hipertrofia</option>
                            <option value="Retorno de Consulta">Retorno de Consulta</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_admin">Data Disponível *</label>
                            <select id="data_admin" name="data_consulta" class="form-control" required>
                                <option value="" disabled selected>Selecione uma data</option>
                                <?php
                                $stmtDatasAdmin = $pdo->query("SELECT DISTINCT data_consulta FROM horarios_disponiveis WHERE status = 'livre' AND data_consulta >= CURDATE() ORDER BY data_consulta ASC");
                                while ($dAdmin = $stmtDatasAdmin->fetch()):
                                ?>
                                    <option value="<?= $dAdmin['data_consulta'] ?>"><?= date('d/m/Y', strtotime($dAdmin['data_consulta'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="horario_admin">Horário Disponível *</label>
                            <select id="horario_admin" name="horario_id" class="form-control" required disabled>
                                <option value="" disabled selected>Escolha uma data primeiro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacoes_admin">Observações Internas (Opcional)</label>
                        <textarea id="observacoes_admin" name="observacoes" class="form-control" rows="3" placeholder="Ex: Paciente preferiu horário de manhã..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit">Realizar Agendamento na Agenda</button>
                </form>
            </div>
        </div>
    </section>

    <section id="gestao-horarios" class="meus-agendamentos-section" style="background-color: var(--light-bg); border-top: 1px solid #E0E0E0;">
        <div class="container">
            <div class="secao-titulo">
                <h2>Liberar Horários na Agenda</h2>
                <p>Gere vários horários de uma só vez para um dia inteiro ou adicione um horário avulso.</p>
            </div>

            <div class="form-container" style="margin-bottom: 30px; border-top: 4px solid #222;">
                <h3 style="margin-bottom: 15px; font-size: 18px; color: #222;"><i class="fas fa-magic"></i> Gerador de Horários em Lote (Dia Inteiro)</h3>
                <form action="admin_acoes.php" method="POST" class="agendamento-form">
                    <input type="hidden" name="acao" value="adicionar_horarios_lote">
                    <div class="grid-gerador-lote">
                        <div class="form-group">
                            <label for="data_lote">Data *</label>
                            <input type="date" id="data_lote" name="data_lote" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_inicio">Início do Expediente *</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" value="08:00" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_fim">Fim do Expediente *</label>
                            <input type="time" id="hora_fim" name="hora_fim" class="form-control" value="18:00" required>
                        </div>
                        <div class="form-group">
                            <label for="intervalo">Duração / Intervalo *</label>
                            <select id="intervalo" name="intervalo" class="form-control" required>
                                <option value="30">A cada 30 minutos</option>
                                <option value="45">A cada 45 minutos</option>
                                <option value="60" selected>A cada 1 hora (60 min)</option>
                                <option value="90">A cada 1h30 (90 min)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-submit">Gerar Todos os Horários do Dia</button>
                </form>
            </div>

            <div class="form-container" style="margin-bottom: 50px;">
                <h3 style="margin-bottom: 15px; font-size: 16px; color: #555;"><i class="far fa-clock"></i> Adicionar Horário Avulso</h3>
                <form action="admin_acoes.php" method="POST" class="agendamento-form">
                    <input type="hidden" name="acao" value="adicionar_horario">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_consulta">Data *</label>
                            <input type="date" id="data_consulta" name="data_consulta" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_consulta">Horário *</label>
                            <input type="time" id="hora_consulta" name="hora_consulta" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-submit" style="background-color: #444; color: #FFF;">Liberar Horário Único</button>
                </form>
            </div>

            <div class="agendamentos-lista">
                <?php foreach ($horarios as $h): ?>
                    <div class="card-agendamento-item">
                        <div class="agendamento-info">
                            <h3><i class="far fa-calendar-check"></i> <?= formatarDataBR($h['data_consulta']) ?> às <?= htmlspecialchars($h['hora_formatada']) ?></h3>
                        </div>
                        <div class="agendamento-acoes" style="display: flex; align-items: center; gap: 15px;">
                            <?php if ($h['status'] === 'livre'): ?>
                                <span class="status-badge status-confirmado">Livre</span>
                                <form action="admin_acoes.php" method="POST">
                                    <input type="hidden" name="acao" value="excluir_horario">
                                    <input type="hidden" name="horario_id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="btn-action btn-cancel" title="Remover horário">
                                        <i class="fas fa-trash"></i> Remover
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="status-badge status-agendado">Ocupado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container footer-content">
            <div class="footer-text">
                <p>&copy; 2026 Dr. Thiago Salomão. Painel de Gestão.</p>
                <p>Desenvolvido por Rafael Marques Fernandes</p>
            </div>
        </div>
    </footer>

    <script>
        const menuIcon = document.getElementById('menu-icon');
        const navbar = document.getElementById('navbar');
        menuIcon.addEventListener('click', () => navbar.classList.toggle('ativo'));

        const filtroData = document.getElementById('filtro_data_tabela');
        const btnLimparFiltro = document.getElementById('btn_limpar_filtro');
        const linhasAgendamentos = document.querySelectorAll('.linha-agendamento');

        if (filtroData && btnLimparFiltro) {
            filtroData.addEventListener('change', () => {
                const dataSelecionada = filtroData.value;
                linhasAgendamentos.forEach(linha => {
                    if (!dataSelecionada || linha.getAttribute('data-data') === dataSelecionada) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                });
            });

            btnLimparFiltro.addEventListener('click', () => {
                filtroData.value = '';
                filtroData.dispatchEvent(new Event('change'));
            });
        }

        function toggleCadastroRapido() {
            const box = document.getElementById('box-cadastro-rapido');
            box.style.display = (box.style.display === 'none' || box.style.display === '') ? 'block' : 'none';
        }

        const inputNovoTel = document.getElementById('novo_telefone');
        if (inputNovoTel) {
            inputNovoTel.addEventListener('input', (e) => {
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

        const selectDataAdmin = document.getElementById('data_admin');
        const selectHorarioAdmin = document.getElementById('horario_admin');

        if (selectDataAdmin) {
            selectDataAdmin.addEventListener('change', async () => {
                const dataEscolhida = selectDataAdmin.value;
                selectHorarioAdmin.innerHTML = '<option value="" disabled selected>Carregando horários...</option>';
                selectHorarioAdmin.disabled = true;

                try {
                    const resposta = await fetch(`buscar_horarios.php?data=${encodeURIComponent(dataEscolhida)}`);
                    const horarios = await resposta.json();

                    selectHorarioAdmin.innerHTML = '';

                    if (horarios.length === 0) {
                        selectHorarioAdmin.innerHTML = '<option value="" disabled selected>Nenhum horário livre nesta data</option>';
                    } else {
                        selectHorarioAdmin.innerHTML = '<option value="" disabled selected>Selecione um horário</option>';
                        horarios.forEach(h => {
                            const option = document.createElement('option');
                            option.value = h.id;
                            option.textContent = h.hora_formatada;
                            selectHorarioAdmin.appendChild(option);
                        });
                        selectHorarioAdmin.disabled = false;
                    }
                } catch (erro) {
                    selectHorarioAdmin.innerHTML = '<option value="" disabled selected>Erro ao carregar horários</option>';
                }
            });
        }

        const inputFiltro = document.getElementById('filtro_paciente');
        const selectPaciente = document.getElementById('busca_paciente');

        if (inputFiltro && selectPaciente) {
            inputFiltro.addEventListener('input', () => {
                const termo = inputFiltro.value.toLowerCase();
                const opcoes = selectPaciente.querySelectorAll('option');

                opcoes.forEach(opcao => {
                    if (opcao.value === "") return;
                    const nome = opcao.getAttribute('data-nome') || '';
                    const tel = opcao.getAttribute('data-tel') || '';
                    opcao.style.display = (nome.includes(termo) || tel.includes(termo)) ? 'block' : 'none';
                });
            });
        }
    </script>
</body>
</html>