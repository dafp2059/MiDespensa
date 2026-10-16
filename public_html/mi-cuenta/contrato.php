<?php
require __DIR__ . '/../../app/bootstrap.php';
$client = require_client();

$contract = contract_full((int) input('id'));
if (!$contract || (int) $contract['client_id'] !== (int) $client['id'] || $contract['status'] === 'cancelado') {
    redirect('mi-cuenta/');
}
view('print_contract', ['contract' => $contract]);
