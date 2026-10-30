<?php
require_once 'enviar_email.php';

define('EVOLUTION_BASE_URL', 'http://' . 'localhost:8080'); 
define('EVOLUTION_INSTANCE', 'ClinicaThiago');         
define('EVOLUTION_API_KEY',  'minha_chave_secreta_123');

function montarTextoWhatsApp($nomePaciente, $especialidade, $dataBR, $hora, $status = 'confirmado') {
    $primeiroNome = explode(' ', trim($nomePaciente))[0];

    if ($status === 'confirmado') {
        return "Olá, *{$primeiroNome}*! Tudo bem?\n\n"
             . "Passando para informar que a sua consulta com o *Dr. Thiago Salomão* está *CONFIRMADA*!\n\n"
             . "*Atendimento:* {$especialidade}\n"
             . "*Data:* {$dataBR}\n"
             . "*Horário:* {$hora}\n"
             . "*Local:* The Mall Villa Bella - Rua Rute Ferraz de Angelis, 886, Sala 50 (2º andar), Campinas - SP.\n\n"
             . "*Lembrete:* Caso possua exames recentes (últimos 6 meses), traga-os no dia da consulta.\n\n"
             . "Qualquer dúvida, estamos à disposição!";
    } elseif ($status === 'cancelado') {
        return "Olá, *{$primeiroNome}*.\n\n"
             . "Informamos que, devido a um imprevisto na agenda, o seu agendamento de *{$especialidade}* para o dia *{$dataBR} às {$hora}* precisou ser *cancelado* pelo Dr. Thiago Salomão.\n\n"
             . "Pedimos desculpas pelo transtorno. Para escolher uma nova data ou horário, acesse o nosso site ou entre em contato conosco pelo WhatsApp: (19) 9 8151-4744.";
    } else {
        return "Olá, *{$primeiroNome}*!\n\n"
             . "Recebemos sua solicitação de agendamento com o *Dr. Thiago Salomão* para *{$dataBR} às {$hora}* ({$especialidade}).\n\n"
             . "Em breve nossa recepção confirmará o seu horário por aqui!";
    }
}

function gerarLinkConfirmacaoWhatsApp($telefone, $nomePaciente, $especialidade, $dataBR, $hora, $status = 'confirmado') {
    $numero = preg_replace('/\D/', '', $telefone);
    if (strlen($numero) <= 11) {
        $numero = '55' . $numero;
    }
    $texto = montarTextoWhatsApp($nomePaciente, $especialidade, $dataBR, $hora, $status);
    $dominioWpp = "https://" . "wa.me/";
    return $dominioWpp . $numero . "?text=" . urlencode($texto);
}

function enviarWhatsAppAPI($telefone, $nomePaciente, $especialidade, $dataBR, $hora, $status = 'confirmado') {
    $apiUrl   = ""; 
    $apiToken = "";

    if (empty($apiUrl)) {
        return false;
    }

    $numero = preg_replace('/\D/', '', $telefone);
    if (strlen($numero) <= 11) {
        $numero = '55' . $numero;
    }

    $texto = montarTextoWhatsApp($nomePaciente, $especialidade, $dataBR, $hora, $status);

    $payload = json_encode([
        "phone"   => $numero,
        "message" => $texto
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Client-Token: {$apiToken}",
        "apikey: {$apiToken}"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}

function enviarEmailConfirmacao($emailDestino, $nomePaciente, $especialidade, $dataBR, $hora, $status = 'agendado') {
    $primeiroNome = explode(' ', trim($nomePaciente))[0];
    
    if ($status === 'confirmado') {
        $assunto = "Consulta Confirmada! - Dr. Thiago Salomão";
        $corDestaque = "#1E8E3E";
        $textoIntro = "Temos o prazer de informar que a sua consulta foi <strong>confirmada</strong> pela nossa equipe.";
    } elseif ($status === 'cancelado') {
        $assunto = "Aviso de Cancelamento de Consulta - Dr. Thiago Salomão";
        $corDestaque = "#D93025";
        $textoIntro = "Informamos que o seu agendamento abaixo foi <strong>cancelado</strong>.";
    } else {
        $assunto = "Solicitação de Agendamento Recebida - Dr. Thiago Salomão";
        $corDestaque = "#E65C00";
        $textoIntro = "Sua solicitação de consulta foi registrada e aguarda a confirmação da recepção.";
    }

    $mensagemHTML = "
    <html>
    <body style='font-family: Montserrat, Arial, sans-serif; background-color: #F5F5F5; color: #4A4A4A; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E0E0E0; border-top: 4px solid {$corDestaque}; padding: 35px;'>
            <h1 style='font-family: Georgia, serif; color: #222222; font-size: 22px; margin-bottom: 5px;'>Dr. Thiago Salomão</h1>
            <span style='color: #E65C00; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; display: block;'>Clínica Médica & Nutrologia</span>
            <h2 style='color: #222222; font-size: 18px;'>Olá, {$primeiroNome}!</h2>
            <p style='line-height: 1.6;'>{$textoIntro}</p>
            <div style='background-color: #FAFAFA; border-left: 3px solid #222222; padding: 15px 20px; margin: 25px 0;'>
                <p style='margin: 8px 0; font-size: 14px; color: #222222;'><strong>Atendimento:</strong> {$especialidade}</p>
                <p style='margin: 8px 0; font-size: 14px; color: #222222;'><strong>Data:</strong> {$dataBR}</p>
                <p style='margin: 8px 0; font-size: 14px; color: #222222;'><strong>Horário:</strong> {$hora}</p>
                <p style='margin: 8px 0; font-size: 14px; color: #222222;'><strong>Local:</strong> The Mall Villa Bella, Rua Rute Ferraz de Angelis, 886 - Sala 50 (2º andar), Campinas - SP</p>
            </div>
            <p style='font-size: 13px;'>WhatsApp da Clínica: <strong>(19) 9 8151-4744</strong></p>
        </div>
    </body>
    </html>";

    return dispararEmailSMTP($emailDestino, $nomePaciente, $assunto, $mensagemHTML);
}

function enviarCodigoWhatsAppAPI($telefone, $nomePaciente, $codigo) {
    $apiUrl   = ""; 
    $apiToken = "";

    if (empty($apiUrl)) {
        return false;
    }

    $primeiroNome = explode(' ', trim($nomePaciente))[0];
    $numero = preg_replace('/\D/', '', $telefone);
    if (strlen($numero) <= 11) {
        $numero = '55' . $numero;
    }

    $mensagem = "Olá, *{$primeiroNome}*!\n\n"
              . "Recebemos uma solicitação para redefinir sua senha na clínica do *Dr. Thiago Salomão*.\n\n"
              . "Seu código de verificação é: *{$codigo}*\n\n"
              . "Válido por 15 minutos. Não compartilhe este código com ninguém.";

    $payload = json_encode([
        "phone"   => $numero,
        "message" => $mensagem
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Client-Token: {$apiToken}",
        "apikey: {$apiToken}"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}