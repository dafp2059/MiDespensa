<?php
// Servidor local de pruebas: imita las reglas de public_html/.htaccess.
// Uso: php -S localhost:8000 -t public_html tools/servidor-local.php
$root = dirname(__DIR__) . '/public_html';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($root . $path) && !str_ends_with($path, '.php')) return false;
if (preg_match('#^/inmueble/([a-z0-9-]+)/?$#', $path, $m)) { $_GET['slug'] = $m[1]; $file = 'inmueble.php'; }
elseif (preg_match('#^/proyecto/([a-z0-9-]+)/?$#', $path, $m)) { $_GET['slug'] = $m[1]; $file = 'proyecto.php'; }
elseif ($path === '/sitemap.xml') $file = 'sitemap.php';
elseif ($path === '/' ) $file = 'index.php';
elseif (str_ends_with($path, '/') && is_file($root . $path . 'index.php')) $file = ltrim($path, '/') . 'index.php';
elseif (is_file($root . $path)) $file = ltrim($path, '/');
elseif (is_file($root . $path . '.php')) $file = ltrim($path, '/') . '.php';
else $file = '404.php';
$_SERVER['SCRIPT_NAME'] = '/' . $file;
chdir(dirname($root . '/' . $file));
require $root . '/' . $file;
