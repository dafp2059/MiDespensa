<?php
/** @var array $page title, description, canonical?, image?, active? */
$title = !empty($page['title']) ? $page['title'] . ' | ' . site('name') : site('name') . ' | Inmobiliaria en ' . site('city');
$description = $page['description'] ?? 'Casas, apartamentos, lotes y locales en venta y arriendo. Asesoría inmobiliaria con ' . site('name') . '.';
$canonical = $page['canonical'] ?? url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
$ogImage = $page['image'] ?? asset('img/og.jpg');
$active = $page['active'] ?? '';
$nav = [
    'inicio' => ['Inicio', url('')],
    'inmuebles' => ['Inmuebles', url('inmuebles')],
    'proyectos' => ['Proyectos', url('proyectos')],
    'propietarios' => ['Vende o arrienda', url('propietarios')],
    'nosotros' => ['Nosotros', url('nosotros')],
    'contacto' => ['Contacto', url('contacto')],
];
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($page['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
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
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
<?php if (!empty($page['jsonld'])): ?>
<script type="application/ld+json"><?= json_encode($page['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<div class="topbar">
  <div class="container topbar__inner">
    <div class="topbar__contact">
      <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= icon('phone') ?> <?= e(phone_display(site('phone'))) ?></a>
      <a href="mailto:<?= e(site('email')) ?>"><?= icon('mail') ?> <?= e(site('email')) ?></a>
    </div>
    <div class="topbar__links">
      <a href="<?= e(url('pagos')) ?>"><?= icon('card') ?> Pagos en línea</a>
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
      <?php foreach ($nav as $key => [$label, $href]): ?>
        <a href="<?= e($href) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn--small nav__cta" href="<?= e(whatsapp_link('Hola, quiero información sobre sus inmuebles.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> WhatsApp</a>
    </nav>
  </div>
</header>

<main id="contenido">
<?php foreach (take_flashes() as $flash): ?>
  <div class="container"><p class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p></div>
<?php endforeach; ?>
