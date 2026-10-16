<?php /** @var array $contract */ ?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($contract['code'] . ' – ' . $contract['client_name']) ?></title>
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
  <article class="doc__body"><?= contract_body_html($contract) ?></article>
</main>
</body>
</html>
