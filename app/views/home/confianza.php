<?php $items = setting_list('home.confianza'); if (!$items) return; ?>
<section class="trust" aria-label="Por qué elegirnos">
  <div class="container trust__grid">
    <?php foreach ($items as $item): ?>
      <div class="trust__item"><?= icon(isset(ICON_CHOICES[$item['icon'] ?? '']) ? $item['icon'] : 'check') ?><div><strong><?= e(tokens((string) ($item['title'] ?? ''))) ?></strong><span><?= e(tokens((string) ($item['text'] ?? ''))) ?></span></div></div>
    <?php endforeach; ?>
  </div>
</section>
