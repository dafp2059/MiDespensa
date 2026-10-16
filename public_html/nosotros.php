<?php
require __DIR__ . '/../app/bootstrap.php';

$agents = query("SELECT agent_name, COUNT(*) AS total FROM properties
    WHERE published = 1 AND agent_name IS NOT NULL AND agent_name <> '' GROUP BY agent_name ORDER BY agent_name")->fetchAll();

$page = [
    'active' => 'nosotros',
    'title' => 'Nosotros',
    'description' => 'Conoce a ' . site('name') . ', inmobiliaria en ' . site('city') . '.',
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Nosotros</p>
    <h1 class="page-head__title"><?= e(site('name')) ?></h1>
    <p class="page-head__lead"><?= e(site('tagline')) ?></p>
  </div>
</section>

<section class="section section--tight">
  <div class="container about">
    <div class="prose">
      <h2>Quiénes somos</h2>
      <p>Somos una inmobiliaria de <?= e(site('city')) ?> dedicada a la compra, venta y arriendo de casas, apartamentos, fincas, lotes, oficinas, locales y bodegas. Acompañamos a familias, inversionistas y propietarios en cada paso, con información clara y trato cercano.</p>
      <h2>Qué hacemos</h2>
      <ul>
        <li><strong>Venta de inmuebles:</strong> valoración, promoción y acompañamiento hasta la escritura.</li>
        <li><strong>Arrendamientos:</strong> búsqueda y estudio de arrendatarios, contratos y recaudo del canon.</li>
        <li><strong>Asesoría de inversión:</strong> te ayudamos a elegir dónde y en qué invertir.</li>
        <li><strong>Proyectos:</strong> desarrollo y comercialización de proyectos inmobiliarios.</li>
      </ul>
    </div>
    <aside class="panel">
      <h2 class="detail__subtitle">Visítanos</h2>
      <ul class="contact-list">
        <li><?= icon('pin') ?> <span><?= e(site('address')) ?>, <?= e(site('city')) ?></span></li>
        <li><?= icon('phone') ?> <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= e(phone_display(site('phone'))) ?></a></li>
        <li><?= icon('mail') ?> <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></li>
        <?php if (site('hours')): ?><li><?= icon('clock') ?> <span><?= e(site('hours')) ?></span></li><?php endif; ?>
      </ul>
      <a class="btn btn--block" href="<?= e(url('contacto')) ?>">Escríbenos</a>
    </aside>
  </div>
</section>

<?php if ($agents): ?>
<section class="section section--sand">
  <div class="container">
    <h2 class="section__title">Nuestro equipo</h2>
    <div class="team">
      <?php foreach ($agents as $a): ?>
        <div class="team__member">
          <span class="agent__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($a['agent_name'], 0, 1))) ?></span>
          <div><strong><?= e($a['agent_name']) ?></strong><span class="muted">Asesor inmobiliario</span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php view('footer'); ?>
