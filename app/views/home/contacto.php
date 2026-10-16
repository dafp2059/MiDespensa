<section class="section">
  <div class="container contact-band">
    <div>
      <?php if (setting('home.contacto.eyebrow') !== ''): ?><p class="eyebrow"><?= t('home.contacto.eyebrow') ?></p><?php endif; ?>
      <h2 class="section__title"><?= t('home.contacto.title') ?></h2>
      <?php if (setting('home.contacto.text') !== ''): ?><p><?= nl2br(t('home.contacto.text')) ?></p><?php endif; ?>
      <?php view('contact_info'); ?>
    </div>
    <?php view('contact_form'); ?>
  </div>
</section>
