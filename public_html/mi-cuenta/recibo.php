<?php
require __DIR__ . '/../../app/bootstrap.php';
$client = require_client();

$charge = receipt_data((int) input('id'), (int) $client['id']);
if (!$charge) {
    redirect('mi-cuenta/');
}
view('print_receipt', ['charge' => $charge]);
