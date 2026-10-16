<?php $steps = setting_list('home.pasos'); ?>
<section class="section">
  <div class="container owners">
    <div class="owners__text">
      <?php if (setting('home.propietarios.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.propietarios.eyebrow') ?></p><?php endif; ?>
      <h2 class="section__title"><?= t('home.propietarios.title') ?></h2>
      <?php if (setting('home.propietarios.text') !== ''): ?><p><?= nl2br(t('home.propietarios.text')) ?></p><?php endif; ?>
      <div class="owners__actions">
        <?php if (setting('home.propietarios.button') !== ''): ?><a class="btn" href="<?= e(link_url(setting('home.propietarios.button_url'))) ?>"><?= t('home.propietarios.button') ?></a><?php endif; ?>
        <?php if (setting('home.propietarios.whatsapp') !== '' && site('whatsapp')): ?><a class="btn btn--ghost" href="<?= e(whatsapp_link('Hola, quiero vender o arrendar mi inmueble.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> <?= t('home.propietarios.whatsapp') ?></a><?php endif; ?>
      </div>
    </div>
    <?php if ($steps): ?>
      <ol class="steps">
        <?php foreach ($steps as $step): ?>
          <li><strong><?= e(tokens((string) ($step['title'] ?? ''))) ?></strong><span><?= e(tokens((string) ($step['text'] ?? ''))) ?></span></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</section>
