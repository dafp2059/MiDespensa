<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$charge = receipt_data((int) input('id'));
if (!$charge) {
    redirect('admin/contratos.php');
}
view('print_receipt', ['charge' => $charge]);
