</main>

<footer class="footer">
  <div class="container footer__grid">
    <div>
      <a class="logo logo--light" href="<?= e(url('')) ?>">
        <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="40" height="40">
        <span>INGESPRO<small>Inmobiliaria</small></span>
      </a>
      <p class="footer__tagline"><?= e(site('tagline')) ?></p>
      <div class="footer__social">
        <?php if (site('instagram')): ?><a href="<?= e(site('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('instagram') ?></a><?php endif; ?>
        <?php if (site('facebook')): ?><a href="<?= e(site('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icon('facebook') ?></a><?php endif; ?>
        <a href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('chat') ?></a>
      </div>
    </div>
    <div>
      <h2 class="footer__title">Inmuebles</h2>
      <ul class="footer__list">
        <li><a href="<?= e(url('inmuebles?operacion=venta')) ?>">En venta</a></li>
        <li><a href="<?= e(url('inmuebles?operacion=arriendo')) ?>">En arriendo</a></li>
        <li><a href="<?= e(url('proyectos')) ?>">Proyectos</a></li>
        <li><a href="<?= e(url('propietarios')) ?>">Vende o arrienda tu inmueble</a></li>
      </ul>
    </div>
    <div>
      <h2 class="footer__title">La empresa</h2>
      <ul class="footer__list">
        <li><a href="<?= e(url('nosotros')) ?>">Nosotros</a></li>
        <li><a href="<?= e(url('contacto')) ?>">Contacto</a></li>
        <li><a href="<?= e(url('pagos')) ?>">Pagos en línea</a></li>
        <li><a href="<?= e(url('politica-de-datos')) ?>">Política de tratamiento de datos</a></li>
      </ul>
    </div>
    <div>
      <h2 class="footer__title">Contacto</h2>
      <ul class="footer__list footer__list--icons">
        <li><?= icon('pin') ?> <span><?= e(site('address')) ?><br><?= e(site('city')) ?></span></li>
        <li><?= icon('phone') ?> <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= e(phone_display(site('phone'))) ?></a></li>
        <li><?= icon('mail') ?> <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></li>
        <?php if (site('hours')): ?><li><?= icon('clock') ?> <span><?= e(site('hours')) ?></span></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="container footer__bottom">
    <p>© <?= date('Y') ?> <?= e(site('name')) ?>. Todos los derechos reservados.</p>
    <a href="<?= e(url('admin/')) ?>">Acceso equipo</a>
  </div>
</footer>

<a class="wa-float" href="<?= e(whatsapp_link('Hola, vengo de la página web y quiero información.')) ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp"><?= icon('chat') ?></a>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
