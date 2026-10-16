<?php
require __DIR__ . '/../app/bootstrap.php';

$page = [
    'active' => 'propietarios',
    'title' => 'Vende o arrienda tu inmueble',
    'description' => '¿Quieres vender o arrendar tu casa, apartamento, local o lote? ' . site('name') . ' se encarga de la valoración, la promoción, las visitas y los trámites.',
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Propietarios</p>
    <h1 class="page-head__title">Vende o arrienda tu inmueble sin complicaciones</h1>
    <p class="page-head__lead">Te acompañamos en todo el proceso para que vendas o arriendes al precio justo y con tranquilidad.</p>
  </div>
</section>

<section class="section section--tight">
  <div class="container owners">
    <div class="owners__text">
      <h2 class="section__title">Qué hacemos por ti</h2>
      <ul class="features features--one">
        <li><?= icon('check') ?> Valoración comercial según el mercado de la zona.</li>
        <li><?= icon('check') ?> Fotos y descripción profesional del inmueble.</li>
        <li><?= icon('check') ?> Publicación en nuestra web, portales inmobiliarios y redes sociales.</li>
        <li><?= icon('check') ?> Atención de interesados y visitas acompañadas.</li>
        <li><?= icon('check') ?> Estudio de arrendatarios y contrato de arrendamiento.</li>
        <li><?= icon('check') ?> Revisión de documentos y acompañamiento hasta la escritura o la entrega.</li>
      </ul>
      <ol class="steps">
        <li><strong>Nos cuentas de tu inmueble</strong><span>Con este formulario o por WhatsApp.</span></li>
        <li><strong>Lo visitamos y lo valoramos</strong><span>Acordamos contigo el precio y las condiciones.</span></li>
        <li><strong>Lo promocionamos</strong><span>Y te mantenemos informado de cada visita.</span></li>
        <li><strong>Cerramos el negocio</strong><span>Con todos los documentos en regla.</span></li>
      </ol>
    </div>
    <div>
      <h2 class="detail__subtitle">Cuéntanos de tu inmueble</h2>
      <?php view('contact_form', ['reason' => 'vender']); ?>
    </div>
  </div>
</section>

<?php view('footer'); ?>
