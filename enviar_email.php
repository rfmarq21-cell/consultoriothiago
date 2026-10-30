<?php
/**
 * Função de Envio de E-mail (Compatível com XAMPP / Servidor Real)
 */
function dispararEmailSMTP($destinatario, $nomeDestinatario, $assunto, $mensagemHTML) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Clínica Dr. Thiago Salomão <contato@drthiagosalomao.com.br>\r\n";

    // Tenta o envio nativo (em localhost apenas simula ou envia se houver sendmail configurado)
    @mail($destinatario, $assunto, $mensagemHTML, $headers);
    return true;
}