<?php
$limit = max(1, min(24, (int) setting('home.destacados.count')));
$featured = query(property_select() . " WHERE p.published = 1
    ORDER BY (p.status = 'disponible') DESC, p.featured DESC, p.created_at DESC LIMIT {$limit}")->fetchAll();
$typeCounts = query("SELECT type, COUNT(*) AS total FROM properties
    WHERE published = 1 AND status IN ('disponible','reservado') GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<section class="section">
  <div class="container">
    <div class="section__head">
      <div>
        <?php if (setting('home.destacados.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.destacados.eyebrow') ?></p><?php endif; ?>
        <h2 class="section__title"><?= t('home.destacados.title') ?></h2>
      </div>
      <?php if (setting('home.destacados.link') !== ''): ?><a class="link-arrow" href="<?= e(url('inmuebles')) ?>"><?= t('home.destacados.link') ?> <?= icon('arrow') ?></a><?php endif; ?>
    </div>

    <?php if ($typeCounts): ?>
      <nav class="chips" aria-label="Buscar por tipo">
        <?php foreach (TYPES_PLURAL as $key => $label): if (empty($typeCounts[$key])) continue; ?>
          <a class="chip" href="<?= e(url('inmuebles?tipo=' . $key)) ?>"><?= e($label) ?> <span><?= (int) $typeCounts[$key] ?></span></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if ($featured): ?>
      <div class="grid-cards">
        <?php foreach ($featured as $p) view('property_card', ['p' => $p]); ?>
      </div>
    <?php else: ?>
      <p class="empty">Muy pronto publicaremos nuevos inmuebles. Escríbenos y te contamos qué tenemos disponible.</p>
    <?php endif; ?>
  </div>
</section>
