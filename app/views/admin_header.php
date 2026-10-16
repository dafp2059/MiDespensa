<?php
/** @var string $title */
$user = current_user();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$unread = $user ? (int) query('SELECT COUNT(*) FROM messages WHERE read_at IS NULL')->fetchColumn() : 0;
$menu = [
    'index.php' => 'Resumen',
    'inmuebles.php' => 'Inmuebles',
    'proyectos.php' => 'Proyectos',
    'ubicaciones.php' => 'Zonas',
    'mensajes.php' => 'Mensajes',
    'pagos.php' => 'Pagos',
];
$designMenu = [
    'banner.php' => 'Banner',
    'inicio.php' => 'Inicio',
    'menus.php' => 'Menús',
    'paginas.php' => 'Páginas',
    'ajustes.php' => 'Ajustes',
    'usuarios.php' => 'Usuarios',
];
$sections = ['inmueble.php' => 'inmuebles.php', 'proyecto.php' => 'proyectos.php', 'pagina.php' => 'paginas.php'];
$editor = !empty($GLOBALS['needs_editor']);
$activeItem = $sections[$current] ?? $current;
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Panel <?= e(site('name')) ?></title>
<link rel="icon" href="<?= e(favicon_url()) ?>">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style><?= theme_css() ?></style>
<?php if ($editor): ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css"><?php endif; ?>
</head>
<body class="admin">
<?php if ($user): ?>
<header class="admin-bar">
  <a class="logo logo--light" href="<?= e(url('admin/')) ?>">
    <?= logo_html(true, 32) ?>
  </a>
  <nav class="admin-nav" aria-label="Panel">
    <?php foreach ($menu as $file => $label): ?>
      <a href="<?= e(url('admin/' . $file)) ?>"<?= $activeItem === $file ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($file === 'mensajes.php' && $unread): ?> <span class="pill"><?= $unread ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    <span class="admin-nav__label">Diseño</span>
    <?php foreach ($designMenu as $file => $label): ?>
      <a href="<?= e(url('admin/' . $file)) ?>"<?= $activeItem === $file ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="admin-bar__user">
    <a href="<?= e(url('')) ?>" target="_blank">Ver web</a>
    <form action="<?= e(url('admin/logout.php')) ?>" method="post"><?= csrf_field() ?><button class="link-button" type="submit">Salir</button></form>
  </div>
</header>
<?php endif; ?>
<main class="admin-main">
<?php foreach (take_flashes() as $flash): ?>
  <p class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endforeach; ?>
