<?php
/** @var array $inv Acta completa (inventory_full). */
$c = $inv['contract'];
$isReception = $inv['type'] === 'recepcion';
$photos = group_by_area($inv['photos']);
$place = implode(', ', array_filter([$c['property_address'] ?: $c['property_title'], $c['property_neighborhood'], $c['property_city']]));
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e(INVENTORY_TYPES[$inv['type']] . ' ' . $c['code']) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/print.css')) ?>">
</head>
<body>
<div class="toolbar no-print">
  <button type="button" onclick="window.print()">Imprimir o guardar como PDF</button>
  <span>En el celular: Compartir → Imprimir → Guardar como PDF.</span>
</div>
<main class="doc">
  <header class="doc__head">
    <?= logo_html() ?>
    <div><strong><?= e(site('legal_name') ?: site('name')) ?></strong><?php if (site('nit')): ?><br>NIT <?= e(site('nit')) ?><?php endif; ?><br><?= e(site('address') . ', ' . site('city')) ?><br><?= e(phone_display(site('phone'))) ?> · <?= e(site('email')) ?></div>
  </header>
  <h1><?= e(mb_strtoupper(INVENTORY_TYPES[$inv['type']])) ?> DE INMUEBLE</h1>
  <table class="doc-table doc-table--plain">
    <tr><th>Contrato</th><td><?= e($c['code'] . ' · ' . CONTRACT_TYPES[$c['type']]) ?></td></tr>
    <tr><th>Inmueble</th><td><?= e($place) ?><?= $c['property_code'] ? ' · Código ' . e($c['property_code']) : '' ?></td></tr>
    <tr><th><?= $c['type'] === 'arriendo' ? 'Arrendatario' : 'Comprador' ?></th><td><?= e($c['client_name'] . ' · ' . $c['client_document_type'] . ' ' . $c['client_document']) ?></td></tr>
    <tr><th>Fecha</th><td><?= e(fecha_larga($inv['inspected_at'])) ?></td></tr>
    <?php if ($inv['keys_count']): ?><tr><th>Llaves</th><td><?= e($inv['keys_count']) ?></td></tr><?php endif; ?>
    <?php if ($inv['meter_water'] || $inv['meter_power'] || $inv['meter_gas']): ?>
      <tr><th>Contadores</th><td><?= e(implode(' · ', array_filter([$inv['meter_water'] ? 'Agua: ' . $inv['meter_water'] : '', $inv['meter_power'] ? 'Energía: ' . $inv['meter_power'] : '', $inv['meter_gas'] ? 'Gas: ' . $inv['meter_gas'] : '']))) ?></td></tr>
    <?php endif; ?>
  </table>

  <?php foreach (group_by_area($inv['items']) as $area => $items):
      $items = array_filter($items, fn($it) => $it['state'] !== 'na');
      if (!$items && empty($photos[$area])) continue; ?>
    <section class="inv-area">
      <h2><?= e($area) ?></h2>
      <?php if ($items): ?>
        <table class="doc-table">
          <thead><tr><th>Elemento</th><?php if ($isReception): ?><th>En la entrega</th><?php endif; ?><th><?= $isReception ? 'Al recibir' : 'Estado' ?></th><th>Observaciones</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): $worse = $isReception && $it['prev_state'] && array_search($it['state'], array_keys(INVENTORY_STATES)) > array_search($it['prev_state'], array_keys(INVENTORY_STATES)); ?>
            <tr<?= $worse ? ' class="worse"' : '' ?>>
              <td><?= e($it['item']) ?></td>
              <?php if ($isReception): ?><td><?= e(INVENTORY_STATES[$it['prev_state']] ?? '') ?><?= $it['prev_notes'] ? '<br><small>' . e($it['prev_notes']) . '</small>' : '' ?></td><?php endif; ?>
              <td><strong><?= e(INVENTORY_STATES[$it['state']] ?? $it['state']) ?></strong></td>
              <td><?= e($it['notes']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <?php if (!empty($photos[$area])): ?>
        <div class="inv-photos"><?php foreach ($photos[$area] as $ph): ?><img src="<?= e(upload_url($ph['filename'])) ?>" alt="Foto de <?= e($area) ?>"><?php endforeach; ?></div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

  <?php if ($inv['notes']): ?><h2>Observaciones generales</h2><p><?= nl2br(e($inv['notes'])) ?></p><?php endif; ?>
  <p>Las partes declaran que revisaron el inmueble y están de acuerdo con el estado descrito en esta acta<?= $c['type'] === 'arriendo' ? ', que hace parte integral del contrato ' . e($c['code']) : '' ?>.</p>
  <div class="signatures">
    <p>_______________________________<br><?= $isReception ? 'RECIBE' : 'ENTREGA' ?><br><?= e(site('legal_name') ?: site('name')) ?></p>
    <p>_______________________________<br><?= $isReception ? 'ENTREGA' : 'RECIBE' ?><br><?= e($c['client_name'] . ' · ' . $c['client_document_type'] . ' ' . $c['client_document']) ?></p>
  </div>
</main>
</body>
</html>
