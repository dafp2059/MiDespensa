<?php
$slides = slides();
$showSearch = setting('hero.search') === '1';
if (!$slides && !$showSearch) return;
$interval = max(3, (int) setting('hero.interval'));
$height = in_array(setting('hero.height'), ['normal', 'alto', 'pantalla'], true) ? setting('hero.height') : 'normal';
?>
<section class="hero hero--<?= e($height) ?><?= count($slides) > 1 ? ' hero--carousel' : '' ?>" aria-roledescription="carrusel" aria-label="Destacados" data-carousel data-interval="<?= $interval ?>">
  <div class="hero__slides">
    <?php foreach ($slides as $i => $s): $ytId = $s['media_type'] === 'youtube' ? youtube_id((string) $s['media']) : null; ?>
      <div class="hero__slide hero__slide--<?= e($s['align']) ?><?= $i === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="diapositiva" aria-label="<?= $i + 1 ?> de <?= count($slides) ?>"<?= $i ? ' aria-hidden="true"' : '' ?> style="--overlay: <?= (int) $s['overlay'] / 100 ?>">
        <?php if ($s['media_type'] === 'image' && $s['media']): ?>
          <img class="hero__media" src="<?= e(upload_url($s['media'])) ?>" alt=""<?= $i ? ' loading="lazy"' : ' fetchpriority="high"' ?>>
        <?php elseif ($s['media_type'] === 'video' && $s['media']): ?>
          <video class="hero__media" src="<?= e(upload_url($s['media'])) ?>" muted loop playsinline preload="<?= $i ? 'none' : 'auto' ?>"<?= $i ? '' : ' autoplay' ?>></video>
        <?php elseif ($ytId): ?>
          <div class="hero__media hero__yt"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($ytId) ?>?autoplay=1&amp;mute=1&amp;loop=1&amp;playlist=<?= e($ytId) ?>&amp;controls=0&amp;modestbranding=1&amp;playsinline=1&amp;rel=0" title="Video de fondo" allow="autoplay; encrypted-media" tabindex="-1" loading="<?= $i ? 'lazy' : 'eager' ?>"></iframe></div>
        <?php endif; ?>
        <div class="container hero__content">
          <?php if ($s['eyebrow']): ?><p class="eyebrow eyebrow--light"><?= e(tokens($s['eyebrow'])) ?></p><?php endif; ?>
          <?php if ($s['title']): ?><<?= $i === 0 ? 'h1' : 'h2' ?> class="hero__title"><?= e(tokens($s['title'])) ?></<?= $i === 0 ? 'h1' : 'h2' ?>><?php endif; ?>
          <?php if ($s['subtitle']): ?><p class="hero__lead"><?= nl2br(e(tokens($s['subtitle']))) ?></p><?php endif; ?>
          <?php if ($s['button_text'] || $s['button2_text']): ?>
            <div class="hero__actions">
              <?php if ($s['button_text']): ?><a class="btn" href="<?= e(link_url((string) $s['button_url'])) ?>"><?= e($s['button_text']) ?></a><?php endif; ?>
              <?php if ($s['button2_text']): ?><a class="btn btn--light" href="<?= e(link_url((string) $s['button2_url'])) ?>"><?= e($s['button2_text']) ?></a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (count($slides) > 1): ?>
    <div class="hero__controls">
      <button class="hero__arrow" type="button" data-prev aria-label="Diapositiva anterior"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button>
      <div class="hero__dots">
        <?php foreach ($slides as $i => $s): ?>
          <button type="button" data-go="<?= $i ?>" aria-label="Ir a la diapositiva <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
      <button class="hero__arrow" type="button" data-next aria-label="Diapositiva siguiente"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></button>
      <button class="hero__arrow" type="button" data-pause aria-label="Pausar"><svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14"/><rect x="14" y="5" width="4" height="14"/></svg></button>
    </div>
  <?php endif; ?>

  <?php if ($showSearch): ?>
    <div class="container hero__search">
      <?php view('search_form', ['compact' => true]); ?>
    </div>
  <?php endif; ?>
</section>
