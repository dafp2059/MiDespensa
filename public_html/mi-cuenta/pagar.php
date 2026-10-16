<?php
require __DIR__ . '/../../app/bootstrap.php';
require APP_DIR . '/wompi.php';
$client = require_client();

if (!is_post()) {
    redirect('mi-cuenta/');
}
csrf_check();
$charge = query("SELECT ch.*, ct.code, ct.type FROM charges ch JOIN contracts ct ON ct.id = ch.contract_id
    WHERE ch.id = ? AND ct.client_id = ? AND ch.status = 'pendiente' AND ct.status <> 'cancelado'", [(int) input('charge_id'), $client['id']])->fetch();
if (!$charge) {
    flash('error', 'Esa cuota ya está pagada o no está disponible.');
    redirect('mi-cuenta/');
}

$reference = 'ING-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
query('INSERT INTO payments (reference, payer_name, payer_document_type, payer_document, payer_email, payer_phone, concept, contract_ref, amount_in_cents, client_id, charge_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
    $reference, $client['name'], $client['document_type'], $client['document'], $client['email'], $client['phone'],
    $charge['type'] === 'arriendo' ? 'canon' : ($charge['concept'] === 'separacion' ? 'separacion' : 'cuota'),
    $charge['code'] . ' · ' . $charge['description'], (int) $charge['amount'] * 100, $client['id'], $charge['id'],
]);
$payment = query('SELECT * FROM payments WHERE reference = ?', [$reference])->fetch();

$page = ['title' => 'Pagar cuota', 'noindex' => true];
view('header', compact('page'));
?>
<section class="section">
  <div class="container narrow">
    <?php view('wompi_checkout', ['payment' => $payment, 'backUrl' => url('mi-cuenta/')]); ?>
  </div>
</section>
<?php view('footer'); ?>
