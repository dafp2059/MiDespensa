<?php
require __DIR__ . '/../../app/bootstrap.php';
$client = require_client();

$inv = inventory_full((int) input('id'));
if (!$inv || !$inv['contract'] || (int) $inv['contract']['client_id'] !== (int) $client['id']) {
    redirect('mi-cuenta/');
}
view('print_inventory', ['inv' => $inv]);
