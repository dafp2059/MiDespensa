<?php
// Carta para notificar el reajuste del canon al arrendatario (art. 20, Ley 820 de 2003).
require __DIR__ . '/../../app/bootstrap.php';
require_can('contratos.ver');

$a = query('SELECT * FROM contract_adjustments WHERE id = ?', [(int) input('id')])->fetch();
$c = $a ? contract_full((int) $a['contract_id']) : null;
if (!$c) {
    redirect('admin/contratos.php');
}
$place = implode(', ', array_filter([$c['property_address'] ?: $c['property_title'], $c['property_neighborhood'], $c['property_city']]));
$pct = str_replace('.', ',', (string) (float) $a['percent']);
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Reajuste de canon <?= e($c['code']) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
</head>
<body>
<div class="toolbar no-print"><button type="button" onclick="window.print()">Imprimir o guardar como PDF</button></div>
<main class="doc">
  <header class="doc__head">
    <?= logo_html() ?>
    <div><strong><?= e(site('legal_name') ?: site('name')) ?></strong><?php if (site('nit')): ?><br>NIT <?= e(site('nit')) ?><?php endif; ?><br><?= e(site('address') . ', ' . site('city')) ?><br><?= e(phone_display(site('phone'))) ?> · <?= e(site('email')) ?></div>
  </header>
  <div class="doc__body">
    <p><?= e(site('city')) ?>, <?= e(fecha_larga(date('Y-m-d', strtotime($a['created_at'])))) ?></p>
    <p>Señor(a)<br><strong><?= e($c['client_name']) ?></strong><br><?= e($c['client_document_type'] . ' ' . $c['client_document']) ?><br><?= e($c['client_address'] ?: $place) ?></p>
    <p><strong>Asunto: reajuste del canon de arrendamiento – contrato <?= e($c['code']) ?></strong></p>
    <p>Respetado(a) señor(a):</p>
    <p>De conformidad con el contrato de arrendamiento <?= e($c['code']) ?> sobre el inmueble ubicado en <?= e($place) ?>, y con el artículo 20 de la Ley 820 de 2003, le informamos que a partir del <strong><?= e(fecha_larga($a['effective_date'])) ?></strong> el canon mensual se reajusta así:</p>
    <table class="doc-table doc-table--plain">
      <tr><th>Canon anterior</th><td><?= e(money($a['old_amount'])) ?></td></tr>
      <tr><th>Reajuste</th><td><?= e($pct) ?> %<?= $a['ipc_value'] !== null ? ' (IPC ' . (int) $a['ipc_year'] . ': ' . e(str_replace('.', ',', (string) (float) $a['ipc_value'])) . ' %)' : '' ?></td></tr>
      <tr class="total"><th>Nuevo canon</th><td><?= e(money($a['new_amount'])) ?><br><small><?= e(pesos_en_letras((int) $a['new_amount'])) ?></small></td></tr>
      <?php if ($c['admin_fee']): ?><tr><th>Administración</th><td><?= e(money($c['admin_fee'])) ?> (sin cambio)</td></tr><?php endif; ?>
    </table>
    <p>El reajuste no supera el ciento por ciento del incremento del índice de precios al consumidor del año calendario inmediatamente anterior. Las cuotas en su portal <?= e(url('mi-cuenta/')) ?> ya reflejan el nuevo valor.</p>
    <p>Cordialmente,</p>
    <p><br>_______________________________<br><?= e(site('legal_name') ?: site('name')) ?></p>
  </div>
</main>
</body>
</html>
