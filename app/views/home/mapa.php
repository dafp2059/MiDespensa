<?php
$points = map_points(query(property_select() . " WHERE p.published = 1 AND p.status IN ('disponible','reservado')
    AND p.latitude IS NOT NULL AND p.longitude IS NOT NULL LIMIT 500")->fetchAll());
if (!$points) return;
?>
<section class="section">
  <div class="container">
    <div class="section__head">
      <div>
        <?php if (setting('home.mapa.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.mapa.eyebrow') ?></p><?php endif; ?>
        <h2 class="section__title"><?= t('home.mapa.title') ?></h2>
      </div>
      <a class="link-arrow" href="<?= e(url('inmuebles?vista=mapa')) ?>">Ver el mapa completo <?= icon('arrow') ?></a>
    </div>
    <div class="map-view" data-map="<?= e(json_encode($points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" role="region" aria-label="Mapa de inmuebles"></div>
  </div>
</section>
