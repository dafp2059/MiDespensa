<?php /** @var array $charge Cuota pagada con datos del contrato y del cliente. */ ?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Recibo <?= e($charge['code'] . '-' . $charge['id']) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
</head>
<body>
<div class="toolbar no-print">
  <button type="button" onclick="window.print()">Imprimir o guardar como PDF</button>
</div>
<main class="doc doc--receipt">
  <header class="doc__head">
    <?= logo_html() ?>
    <div><strong><?= e(site('legal_name') ?: site('name')) ?></strong><?php if (site('nit')): ?><br>NIT <?= e(site('nit')) ?><?php endif; ?><br><?= e(site('address') . ', ' . site('city')) ?><br><?= e(phone_display(site('phone'))) ?> · <?= e(site('email')) ?></div>
  </header>
  <h1>Recibo de pago n.º <?= e($charge['code'] . '-' . str_pad((string) $charge['id'], 5, '0', STR_PAD_LEFT)) ?></h1>
  <table class="doc-table doc-table--plain">
    <tr><th>Fecha del pago</th><td><?= e(fecha_larga($charge['paid_at'])) ?></td></tr>
    <tr><th>Recibimos de</th><td><?= e($charge['client_name'] . ' · ' . $charge['client_document_type'] . ' ' . $charge['client_document']) ?></td></tr>
    <tr><th>Por concepto de</th><td><?= e($charge['description']) ?></td></tr>
    <tr><th>Contrato</th><td><?= e($charge['code'] . ' · ' . CONTRACT_TYPES[$charge['type']]) ?></td></tr>
    <tr><th>Inmueble</th><td><?= e(implode(', ', array_filter([$charge['property_address'] ?: $charge['property_title'], $charge['property_city']]))) ?></td></tr>
    <tr><th>Medio de pago</th><td><?= e($charge['paid_method']) ?><?= $charge['reference'] ? ' · Ref. ' . e($charge['reference']) : '' ?></td></tr>
    <tr class="total"><th>Valor</th><td><?= e(money($charge['amount'])) ?><br><small><?= e(pesos_en_letras((int) $charge['amount'])) ?></small></td></tr>
  </table>
  <p class="doc__note">Este recibo se generó electrónicamente en <?= e(url('')) ?>.</p>
</main>
</body>
</html>
