<?php /** @var array $p */ ?>
<article class="card<?= is_closed($p) ? ' card--closed' : '' ?>">
  <a class="card__media" href="<?= e(property_url($p)) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(upload_url($p['cover'] ?? null)) ?>" alt="" loading="lazy">
    <span class="badge badge--<?= e($p['operation']) ?>"><?= e(OPERATIONS[$p['operation']]) ?></span>
    <?php if ($p['status'] !== 'disponible'): ?>
      <span class="badge badge--status"><?= e(STATUSES[$p['status']]) ?></span>
    <?php endif; ?>
  </a>
  <div class="card__body">
    <p class="card__type"><?= e(TYPES[$p['type']]) ?><?= property_place($p) ? ' · ' . e(property_place($p)) : '' ?><?php if ($p['code']): ?><span class="card__code">Cód. <?= e($p['code']) ?></span><?php endif; ?></p>
    <h3 class="card__title"><a href="<?= e(property_url($p)) ?>"><?= e($p['title']) ?></a></h3>
    <p class="card__price">
      <?= $p['price'] ? e(money($p['price'])) : 'Precio a convenir' ?>
      <?php if ($p['operation'] === 'arriendo' && $p['price']): ?><small>/ mes</small><?php endif; ?>
    </p>
    <ul class="card__facts">
      <?php if ($p['bedrooms'] !== null): ?><li><?= icon('bed') ?> <?= (int) $p['bedrooms'] ?> <span>hab.</span></li><?php endif; ?>
      <?php if ($p['bathrooms'] !== null): ?><li><?= icon('bath') ?> <?= (int) $p['bathrooms'] ?> <span>baños</span></li><?php endif; ?>
      <?php if ($p['area_built'] !== null): ?><li><?= icon('area') ?> <?= e(number_es($p['area_built'])) ?> <span>m²</span></li><?php elseif ($p['area_lot'] !== null): ?><li><?= icon('area') ?> <?= e(number_es($p['area_lot'])) ?> <span>m² lote</span></li><?php endif; ?>
      <?php if ($p['parking']): ?><li><?= icon('car') ?> <?= (int) $p['parking'] ?> <span>parq.</span></li><?php endif; ?>
    </ul>
  </div>
</article>
