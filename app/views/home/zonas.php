<?php
$zones = query("SELECT l.*, COUNT(p.id) AS total FROM locations l
    LEFT JOIN properties p ON p.location_id = l.id AND p.published = 1 AND p.status IN ('disponible','reservado')
    WHERE l.featured = 1 GROUP BY l.id ORDER BY l.sort_order, l.name")->fetchAll();
if (!$zones) return;
?>
<section class="section section--sand">
  <div class="container">
    <div class="section__head">
      <div>
        <?php if (setting('home.zonas.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.zonas.eyebrow') ?></p><?php endif; ?>
        <h2 class="section__title"><?= t('home.zonas.title') ?></h2>
      </div>
    </div>
    <div class="zones">
      <?php foreach ($zones as $z): ?>
        <a class="zone" href="<?= e(url('inmuebles?ubicacion=' . $z['slug'])) ?>"<?= $z['image'] ? ' style="--zone-image:url(\'' . e(upload_url($z['image'])) . '\')"' : '' ?>>
          <span class="zone__name"><?= e($z['name']) ?></span>
          <span class="zone__count"><?= (int) $z['total'] === 1 ? '1 inmueble disponible' : (int) $z['total'] . ' inmuebles disponibles' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
