<?php
$projects = query("SELECT pr.*, (SELECT i.filename FROM images i WHERE i.owner_type = 'project' AND i.owner_id = pr.id
    ORDER BY i.sort_order, i.id LIMIT 1) AS cover FROM projects pr WHERE pr.published = 1
    ORDER BY pr.sort_order, pr.created_at DESC LIMIT 3")->fetchAll();
if (!$projects) return;
?>
<section class="section section--sand">
  <div class="container">
    <div class="section__head">
      <div>
        <?php if (setting('home.proyectos.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.proyectos.eyebrow') ?></p><?php endif; ?>
        <h2 class="section__title"><?= t('home.proyectos.title') ?></h2>
      </div>
      <?php if (setting('home.proyectos.link') !== ''): ?><a class="link-arrow" href="<?= e(url('proyectos')) ?>"><?= t('home.proyectos.link') ?> <?= icon('arrow') ?></a><?php endif; ?>
    </div>
    <div class="grid-cards">
      <?php foreach ($projects as $pr): ?>
        <article class="card">
          <a class="card__media" href="<?= e(project_url($pr)) ?>" tabindex="-1" aria-hidden="true">
            <img src="<?= e(upload_url($pr['cover'])) ?>" alt="" loading="lazy">
            <?php if ($pr['status']): ?><span class="badge badge--venta"><?= e($pr['status']) ?></span><?php endif; ?>
          </a>
          <div class="card__body">
            <p class="card__type"><?= e($pr['location_text']) ?></p>
            <h3 class="card__title"><a href="<?= e(project_url($pr)) ?>"><?= e($pr['name']) ?></a></h3>
            <?php if ($pr['price_from']): ?><p class="card__price"><small>Desde</small> <?= e(money($pr['price_from'])) ?></p><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
