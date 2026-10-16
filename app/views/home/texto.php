<?php $content = rich(setting('home.texto.content')); if ($content === '' && setting('home.texto.title') === '') return; ?>
<section class="section">
  <div class="container narrow">
    <?php if (setting('home.texto.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.texto.eyebrow') ?></p><?php endif; ?>
    <?php if (setting('home.texto.title') !== ''): ?><h2 class="section__title"><?= t('home.texto.title') ?></h2><?php endif; ?>
    <div class="prose"><?= $content ?></div>
    <?php if (setting('home.texto.button') !== ''): ?><p><a class="btn" href="<?= e(link_url(setting('home.texto.button_url'))) ?>"><?= t('home.texto.button') ?></a></p><?php endif; ?>
  </div>
</section>
