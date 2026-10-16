<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$contract = contract_full((int) input('id'));
if (!$contract) {
    redirect('admin/contratos.php');
}
view('print_contract', ['contract' => $contract]);
