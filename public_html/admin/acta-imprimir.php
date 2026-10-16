<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('contratos.ver');

$inv = inventory_full((int) input('id'));
if (!$inv || !$inv['contract']) {
    redirect('admin/contratos.php');
}
view('print_inventory', ['inv' => $inv]);
