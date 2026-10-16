<?php
if (!function_exists('view')) {
    require __DIR__ . '/../app/bootstrap.php';
}
http_response_code(404);
$page = ['title' => 'Página no encontrada', 'noindex' => true];
view('header', compact('page'));
?>
<section class="section">
  <div class="container narrow">
    <h1 class="page-head__title">No encontramos esta página</h1>
    <p>Es posible que el inmueble ya no esté publicado o que la dirección haya cambiado.</p>
    <div class="owners__actions">
      <a class="btn" href="<?= e(url('inmuebles')) ?>">Ver inmuebles disponibles</a>
      <a class="btn btn--ghost" href="<?= e(url('')) ?>">Ir al inicio</a>
    </div>
  </div>
</section>
<?php view('footer'); ?>
