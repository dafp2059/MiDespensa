</main>

<footer class="footer">
  <div class="container footer__grid">
    <div>
      <a class="logo logo--light" href="<?= e(url('')) ?>">
        <?= logo_html(true) ?>
      </a>
      <?php if (site('tagline')): ?><p class="footer__tagline"><?= e(site('tagline')) ?></p><?php endif; ?>
      <div class="footer__social">
        <?php if (site('instagram')): ?><a href="<?= e(site('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram') ?></a><?php endif; ?>
        <?php if (site('facebook')): ?><a href="<?= e(site('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a><?php endif; ?>
        <?php if (site('whatsapp')): ?><a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('chat') ?></a><?php endif; ?>
      </div>
    </div>
    <?php foreach (['pie_1' => 'footer.col1_title', 'pie_2' => 'footer.col2_title'] as $menu => $titleKey): $items = menu_items($menu); if (!$items) continue; ?>
      <div>
        <?php if (setting($titleKey) !== ''): ?><h2 class="footer__title"><?= t($titleKey) ?></h2><?php endif; ?>
        <ul class="footer__list">
          <?php foreach ($items as $item): ?>
            <li><a href="<?= e(link_url($item['url'] ?? '')) ?>"<?= !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
    <div>
      <?php if (setting('footer.contact_title') !== ''): ?><h2 class="footer__title"><?= t('footer.contact_title') ?></h2><?php endif; ?>
      <ul class="footer__list footer__list--icons">
        <?php if (site('address')): ?><li><?= icon('pin') ?> <span><?= e(site('address')) ?><br><?= e(site('city')) ?></span></li><?php endif; ?>
        <?php if (site('phone')): ?><li><?= icon('phone') ?> <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= e(phone_display(site('phone'))) ?></a></li><?php endif; ?>
        <?php if (site('email')): ?><li><?= icon('mail') ?> <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></li><?php endif; ?>
        <?php if (site('hours')): ?><li><?= icon('clock') ?> <span><?= e(site('hours')) ?></span></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="container footer__bottom">
    <p><?= t('footer.copyright') ?></p>
    <?php if (setting('footer.team_link') === '1'): ?><a href="<?= e(url('admin/')) ?>">Acceso equipo</a><?php endif; ?>
  </div>
</footer>

<?php if (setting('whatsapp.float') === '1' && site('whatsapp')): ?>
  <a class="wa-float" href="<?= e(whatsapp_link(tokens(setting('whatsapp.message')))) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><?= icon('chat') ?></a>
<?php endif; ?>

<?php if (!empty($GLOBALS['needs_map'])): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script><?php endif; ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
