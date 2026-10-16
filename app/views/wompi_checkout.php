<?php
/** Resumen del pago y botón que lleva a Wompi. @var array $payment  @var string $backUrl */
$backUrl = $backUrl ?? url('pagos');
?>
      <div class="panel pay__confirm">
        <h2 class="detail__subtitle">Confirma tu pago</h2>
        <dl class="summary">
          <div><dt>Nombre</dt><dd><?= e($payment['payer_name']) ?></dd></div>
          <div><dt>Documento</dt><dd><?= e($payment['payer_document_type'] . ' ' . $payment['payer_document']) ?></dd></div>
          <div><dt>Concepto</dt><dd><?= e(PAYMENT_CONCEPTS[$payment['concept']] ?? $payment['concept']) ?></dd></div>
          <div><dt>Contrato / inmueble</dt><dd><?= e($payment['contract_ref']) ?></dd></div>
          <div><dt>Referencia</dt><dd><?= e($payment['reference']) ?></dd></div>
          <div class="summary__total"><dt>Total a pagar</dt><dd><?= e(money($payment['amount_in_cents'] / 100)) ?></dd></div>
        </dl>
        <form action="https://checkout.wompi.co/p/" method="get">
          <?php foreach (wompi_checkout_fields($payment) as $name => $value): if ($value === '') continue; ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
          <?php endforeach; ?>
          <button class="btn btn--block" type="submit"><?= icon('card') ?> Pagar con Wompi</button>
        </form>
        <p class="muted">Serás redirigido a Wompi (Bancolombia) para completar el pago de forma segura. <a href="<?= e($backUrl) ?>">Volver</a></p>
        <?php if (wompi_is_test()): ?><p class="alert alert--error">Modo de pruebas: no se cobrará dinero real.</p><?php endif; ?>
      </div>
