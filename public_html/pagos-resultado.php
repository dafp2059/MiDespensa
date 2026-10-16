<?php
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/wompi.php';

// Wompi redirige aquí con ?id=<id de la transacción>.
$payment = null;
$tx = wompi_fetch_transaction(input('id'));
if ($tx) {
    $payment = wompi_apply_transaction($tx);
}
$status = $payment['status'] ?? null;

$messages = [
    'APPROVED' => ['success', '¡Pago aprobado!', 'Gracias. Recibimos tu pago y te enviamos el comprobante al correo.'],
    'PENDING' => ['info', 'Pago en proceso', 'Tu banco aún está confirmando el pago. Te avisaremos por correo cuando se apruebe.'],
    'DECLINED' => ['error', 'Pago rechazado', 'El banco no aprobó el pago. Puedes intentarlo de nuevo con otro medio de pago.'],
    'VOIDED' => ['error', 'Pago anulado', 'La transacción fue anulada.'],
    'ERROR' => ['error', 'No pudimos procesar el pago', 'Ocurrió un error con la transacción. Inténtalo de nuevo o escríbenos.'],
];
[$type, $title, $text] = $messages[$status] ?? ['error', 'No encontramos el pago', 'Si hiciste un pago y no ves la confirmación, escríbenos por WhatsApp con tu referencia.'];

$page = ['title' => $title, 'noindex' => true];
view('header', compact('page'));
?>

<section class="section">
  <div class="container narrow">
    <div class="panel result result--<?= e($type) ?>">
      <h1 class="page-head__title"><?= e($title) ?></h1>
      <p><?= e($text) ?></p>
      <?php if ($payment): ?>
        <dl class="summary">
          <div><dt>Referencia</dt><dd><?= e($payment['reference']) ?></dd></div>
          <div><dt>Concepto</dt><dd><?= e(PAYMENT_CONCEPTS[$payment['concept']] ?? $payment['concept']) ?></dd></div>
          <div><dt>Valor</dt><dd><?= e(money($payment['amount_in_cents'] / 100)) ?></dd></div>
          <div><dt>Estado</dt><dd><?= e(PAYMENT_STATUSES[$payment['status']] ?? $payment['status']) ?></dd></div>
        </dl>
      <?php endif; ?>
      <div class="owners__actions">
        <?php if (in_array($status, ['DECLINED', 'ERROR', 'VOIDED', null], true)): ?>
          <a class="btn" href="<?= e(url('pagos')) ?>">Intentar de nuevo</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(whatsapp_link('Hola, tengo una pregunta sobre mi pago' . ($payment ? ' con referencia ' . $payment['reference'] : '') . '.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Escribir por WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<?php view('footer'); ?>
