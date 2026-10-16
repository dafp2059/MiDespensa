<?php
// URL de eventos para configurar en Wompi: https://tudominio.com/wompi-webhook.php
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/wompi.php';

header('Content-Type: application/json');

if (!is_post()) {
    http_response_code(405);
    exit('{"error":"method"}');
}

$event = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($event) || !wompi_valid_event($event)) {
    http_response_code(401);
    exit('{"error":"signature"}');
}

if (($event['event'] ?? '') === 'transaction.updated' && is_array($event['data']['transaction'] ?? null)) {
    wompi_apply_transaction($event['data']['transaction']);
}

echo '{"ok":true}';
