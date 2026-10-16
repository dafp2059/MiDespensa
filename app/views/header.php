<?php
/** @var array $page title, description, canonical?, image?, noindex?, jsonld? */
$title = !empty($page['title']) ? $page['title'] . ' | ' . site('name') : site('name') . ' | Inmobiliaria en ' . site('city');
$description = $page['description'] ?? 'Casas, apartamentos, lotes y locales en venta y arriendo. Asesoría inmobiliaria con ' . site('name') . '.';
$canonical = $page['canonical'] ?? url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
$ogImage = $page['image'] ?? asset('img/og.jpg');
$phoneDigits = preg_replace('/\D/', '', site('phone'));
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($page['noindex']) || config('noindex')): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:locale" content="es_CO">
<meta property="og:site_name" content="<?= e(site('name')) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= e(favicon_url()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= e(fonts_url()) ?>" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
<style><?= theme_css() ?></style>
<?php if (!empty($page['jsonld'])): ?>
<script type="application/ld+json"><?= json_encode($page['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<div class="topbar">
  <div class="container topbar__inner">
    <div class="topbar__contact">
      <?php if ($phoneDigits): ?><a href="tel:+57<?= e($phoneDigits) ?>"><?= icon('phone') ?> <?= e(phone_display(site('phone'))) ?></a><?php endif; ?>
      <?php if (site('email')): ?><a href="mailto:<?= e(site('email')) ?>"><?= icon('mail') ?> <?= e(site('email')) ?></a><?php endif; ?>
    </div>
    <div class="topbar__links">
      <?php foreach (menu_items('superior') as $item): ?>
        <a href="<?= e(link_url($item['url'] ?? '')) ?>"<?= !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?></a>
      <?php endforeach; ?>
      <?php if (site('instagram')): ?><a href="<?= e(site('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram') ?></a><?php endif; ?>
      <?php if (site('facebook')): ?><a href="<?= e(site('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a><?php endif; ?>
    </div>
  </div>
</div>

<header class="header">
  <div class="container header__inner">
    <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e(site('name')) ?>, ir al inicio">
      <?= logo_html() ?>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu" aria-label="Abrir menú"><?= icon('menu') ?></button>
    <nav id="menu" class="nav" aria-label="Principal">
      <?php foreach (menu_items('principal') as $item): ?>
        <a href="<?= e(link_url($item['url'] ?? '')) ?>"<?= is_current_link($item['url'] ?? '') ? ' aria-current="page"' : '' ?><?= !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?></a>
      <?php endforeach; ?>
      <?php if (setting('whatsapp.nav_button') === '1' && site('whatsapp')): ?>
        <a class="btn btn--small nav__cta" href="<?= e(whatsapp_link(tokens(setting('whatsapp.message')))) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> <?= t('whatsapp.nav_label') ?></a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="contenido">
<?php foreach (take_flashes() as $flash): ?>
  <div class="container"><p class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p></div>
<?php endforeach; ?>
