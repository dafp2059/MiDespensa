<?php
require __DIR__ . '/../app/bootstrap.php';

$projects = query("SELECT pr.*, (SELECT i.filename FROM images i WHERE i.owner_type = 'project' AND i.owner_id = pr.id
    ORDER BY i.sort_order, i.id LIMIT 1) AS cover FROM projects pr WHERE pr.published = 1
    ORDER BY pr.sort_order, pr.created_at DESC")->fetchAll();

$page = [
    'active' => 'proyectos',
    'title' => 'Proyectos',
    'description' => 'Proyectos inmobiliarios de ' . site('name') . ': ubicación, avance y precios desde.',
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Proyectos</p>
    <h1 class="page-head__title">Nuestros proyectos</h1>
    <p class="page-head__lead">Conoce los proyectos que construimos y comercializamos.</p>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <?php if ($projects): ?>
      <div class="grid-cards">
        <?php foreach ($projects as $pr): ?>
          <article class="card">
            <a class="card__media" href="<?= e(project_url($pr)) ?>" tabindex="-1" aria-hidden="true">
              <img src="<?= e(upload_url($pr['cover'])) ?>" alt="" loading="lazy">
              <?php if ($pr['status']): ?><span class="badge badge--venta"><?= e($pr['status']) ?></span><?php endif; ?>
            </a>
            <div class="card__body">
              <p class="card__type"><?= e($pr['location_text']) ?></p>
              <h2 class="card__title"><a href="<?= e(project_url($pr)) ?>"><?= e($pr['name']) ?></a></h2>
              <?php if ($pr['price_from']): ?><p class="card__price"><small>Desde</small> <?= e(money($pr['price_from'])) ?></p><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty">Pronto publicaremos nuestros proyectos. Escríbenos para conocer los que están en curso.</p>
    <?php endif; ?>
  </div>
</section>

<?php view('footer'); ?>
