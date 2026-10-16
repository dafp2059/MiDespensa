<?php
require __DIR__ . '/../app/bootstrap.php';

$pr = query('SELECT * FROM projects WHERE slug = ? AND published = 1', [input('slug')])->fetch();
if (!$pr) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$images = images_for('project', (int) $pr['id']);

$page = [
    'active' => 'proyectos',
    'title' => $pr['name'] . ($pr['location_text'] ? ' – ' . $pr['location_text'] : ''),
    'description' => mb_strimwidth(preg_replace('/\s+/', ' ', (string) $pr['description']) ?: 'Proyecto ' . $pr['name'], 0, 158, '…'),
    'canonical' => project_url($pr),
    'image' => $images ? upload_url($images[0]['filename']) : null,
];
view('header', compact('page'));
?>

<section class="section section--tight">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Ruta">
      <a href="<?= e(url('')) ?>">Inicio</a> / <a href="<?= e(url('proyectos')) ?>">Proyectos</a> / <span aria-current="page"><?= e($pr['name']) ?></span>
    </nav>
    <div class="detail__head">
      <div>
        <?php if ($pr['status']): ?><div class="detail__badges"><span class="badge badge--venta"><?= e($pr['status']) ?></span></div><?php endif; ?>
        <h1 class="detail__title"><?= e($pr['name']) ?></h1>
        <?php if ($pr['location_text']): ?><p class="detail__place"><?= icon('pin') ?> <?= e($pr['location_text']) ?></p><?php endif; ?>
      </div>
      <?php if ($pr['price_from']): ?><p class="detail__price"><small>Desde</small> <?= e(money($pr['price_from'])) ?></p><?php endif; ?>
    </div>

    <div class="gallery" data-gallery>
      <figure class="gallery__main">
        <img src="<?= e(upload_url($images[0]['filename'] ?? null)) ?>" alt="<?= e($pr['name']) ?>, foto 1" data-gallery-main>
      </figure>
      <?php if (count($images) > 1): ?>
        <div class="gallery__thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" data-src="<?= e(upload_url($img['filename'])) ?>" data-alt="<?= e($pr['name']) ?>, foto <?= $i + 1 ?>" aria-label="Ver foto <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
              <img src="<?= e(upload_url($img['filename'])) ?>" alt="" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="detail">
      <div class="detail__main">
        <?php if ($pr['description']): ?>
          <h2 class="detail__subtitle">Sobre el proyecto</h2>
          <div class="prose"><?= nl2br(e($pr['description'])) ?></div>
        <?php endif; ?>
      </div>
      <aside class="detail__aside">
        <div class="panel agent">
          <p>¿Te interesa este proyecto? Te enviamos planos, precios y formas de pago.</p>
          <a class="btn btn--block btn--wa" href="<?= e(whatsapp_link('Hola, quiero información del proyecto ' . $pr['name'] . ': ' . project_url($pr))) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Pedir información</a>
          <a class="btn btn--block btn--ghost" href="<?= e(url('contacto?motivo=inversion')) ?>">Escribir un mensaje</a>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php view('footer'); ?>
