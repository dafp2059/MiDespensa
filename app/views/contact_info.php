<ul class="contact-list<?= !empty($big) ? ' contact-list--big' : '' ?>">
  <?php if (site('whatsapp') && !empty($big)): ?><li><?= icon('chat') ?> <a href="<?= e(whatsapp_link(tokens(setting('whatsapp.message')))) ?>" target="_blank" rel="noopener">WhatsApp <?= e(phone_display(site('phone'))) ?></a></li><?php endif; ?>
  <?php if (site('phone')): ?><li><?= icon('phone') ?> <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= e(phone_display(site('phone'))) ?></a></li><?php endif; ?>
  <?php if (site('email')): ?><li><?= icon('mail') ?> <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></li><?php endif; ?>
  <?php if (site('address')): ?><li><?= icon('pin') ?> <span><?= e(site('address')) ?>, <?= e(site('city')) ?></span></li><?php endif; ?>
  <?php if (site('hours') && !empty($big)): ?><li><?= icon('clock') ?> <span><?= e(site('hours')) ?></span></li><?php endif; ?>
</ul>
