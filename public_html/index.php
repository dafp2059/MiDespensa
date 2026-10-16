<?php
require __DIR__ . '/../app/bootstrap.php';

$featured = query(property_select() . " WHERE p.published = 1
    ORDER BY (p.status = 'disponible') DESC, p.featured DESC, p.created_at DESC LIMIT 6")->fetchAll();

$typeCounts = query("SELECT type, COUNT(*) AS total FROM properties
    WHERE published = 1 AND status IN ('disponible','reservado') GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);

$zones = query("SELECT l.*, COUNT(p.id) AS total FROM locations l
    LEFT JOIN properties p ON p.location_id = l.id AND p.published = 1 AND p.status IN ('disponible','reservado')
    WHERE l.featured = 1 GROUP BY l.id ORDER BY l.sort_order, l.name")->fetchAll();

$projects = query("SELECT pr.*, (SELECT i.filename FROM images i WHERE i.owner_type = 'project' AND i.owner_id = pr.id
    ORDER BY i.sort_order, i.id LIMIT 1) AS cover FROM projects pr WHERE pr.published = 1
    ORDER BY pr.sort_order, pr.created_at DESC LIMIT 3")->fetchAll();

$heroImage = null;
foreach ($featured as $p) {
    if ($p['cover']) {
        $heroImage = upload_url($p['cover']);
        break;
    }
}

$page = [
    'active' => 'inicio',
    'description' => 'Inmobiliaria en ' . site('city') . '. Casas, apartamentos, fincas, lotes y locales en venta y arriendo, con asesoría personalizada.',
    'canonical' => url(''),
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateAgent',
        'name' => site('name'),
        'url' => url(''),
        'telephone' => '+57' . preg_replace('/\D/', '', site('phone')),
        'email' => site('email'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => site('address'), 'addressLocality' => site('city'), 'addressCountry' => 'CO'],
        'sameAs' => array_values(array_filter([site('instagram'), site('facebook')])),
    ],
];
view('header', compact('page'));
?>

<section class="hero"<?= $heroImage ? ' style="--hero-image:url(\'' . e($heroImage) . '\')"' : '' ?>>
  <div class="container hero__inner">
    <p class="eyebrow eyebrow--light">Inmobiliaria en <?= e(site('city')) ?></p>
    <h1 class="hero__title">Encuentra tu próximo hogar, o el comprador ideal para el tuyo</h1>
    <p class="hero__lead">Casas, apartamentos, fincas y locales en venta y arriendo. Te acompañamos desde la primera visita hasta la firma.</p>
    <?php view('search_form', ['compact' => true]); ?>
  </div>
</section>

<section class="trust" aria-label="Por qué elegirnos">
  <div class="container trust__grid">
    <div class="trust__item"><?= icon('shield') ?><div><strong>Asesoría legal y técnica</strong><span>Revisamos títulos y documentos antes de cada negocio.</span></div></div>
    <div class="trust__item"><?= icon('clock') ?><div><strong>Respuesta en 24 horas</strong><span>Por WhatsApp, teléfono o correo, como prefieras.</span></div></div>
    <div class="trust__item"><?= icon('camera') ?><div><strong>Visitas acompañadas</strong><span>Un asesor te muestra el inmueble y resuelve tus dudas.</span></div></div>
    <div class="trust__item"><?= icon('card') ?><div><strong>Pagos en línea</strong><span>Paga tu canon o tu separación con Wompi, sin filas.</span></div></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section__head">
      <div>
        <p class="eyebrow">Inmuebles destacados</p>
        <h2 class="section__title">Propiedades seleccionadas para ti</h2>
      </div>
      <a class="link-arrow" href="<?= e(url('inmuebles')) ?>">Ver todos los inmuebles <?= icon('arrow') ?></a>
    </div>

    <?php if ($typeCounts): ?>
      <nav class="chips" aria-label="Buscar por tipo">
        <?php foreach (TYPES_PLURAL as $key => $label): if (empty($typeCounts[$key])) continue; ?>
          <a class="chip" href="<?= e(url('inmuebles?tipo=' . $key)) ?>"><?= e($label) ?> <span><?= (int) $typeCounts[$key] ?></span></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if ($featured): ?>
      <div class="grid-cards">
        <?php foreach ($featured as $p) view('property_card', ['p' => $p]); ?>
      </div>
    <?php else: ?>
      <p class="empty">Muy pronto publicaremos nuevos inmuebles. Escríbenos y te contamos qué tenemos disponible.</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($zones): ?>
<section class="section section--sand">
  <div class="container">
    <div class="section__head">
      <div>
        <p class="eyebrow">Zonas</p>
        <h2 class="section__title">Dónde trabajamos</h2>
      </div>
    </div>
    <div class="zones">
      <?php foreach ($zones as $z): ?>
        <a class="zone" href="<?= e(url('inmuebles?ubicacion=' . $z['slug'])) ?>"<?= $z['image'] ? ' style="--zone-image:url(\'' . e(upload_url($z['image'])) . '\')"' : '' ?>>
          <span class="zone__name"><?= e($z['name']) ?></span>
          <span class="zone__count"><?= (int) $z['total'] === 1 ? '1 inmueble disponible' : (int) $z['total'] . ' inmuebles disponibles' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container owners">
    <div class="owners__text">
      <p class="eyebrow">Propietarios</p>
      <h2 class="section__title">¿Quieres vender o arrendar tu inmueble?</h2>
      <p>Nos encargamos de todo: avalúo comercial, fotos profesionales, publicación en portales, filtro de interesados, estudio de arrendatarios y acompañamiento legal hasta la firma.</p>
      <div class="owners__actions">
        <a class="btn" href="<?= e(url('propietarios')) ?>">Quiero vender o arrendar</a>
        <a class="btn btn--ghost" href="<?= e(whatsapp_link('Hola, quiero vender o arrendar mi inmueble.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Hablar por WhatsApp</a>
      </div>
    </div>
    <ol class="steps">
      <li><strong>Nos cuentas de tu inmueble</strong><span>Por el formulario, WhatsApp o una llamada.</span></li>
      <li><strong>Lo visitamos y lo valoramos</strong><span>Te damos un precio realista según el mercado de la zona.</span></li>
      <li><strong>Lo promocionamos</strong><span>Fotos, publicación en portales y redes, y visitas con interesados filtrados.</span></li>
      <li><strong>Cerramos el negocio</strong><span>Revisamos documentos, promesa de compraventa o contrato de arriendo.</span></li>
    </ol>
  </div>
</section>

<?php if ($projects): ?>
<section class="section section--sand">
  <div class="container">
    <div class="section__head">
      <div>
        <p class="eyebrow">Proyectos</p>
        <h2 class="section__title">Nuestros proyectos</h2>
      </div>
      <a class="link-arrow" href="<?= e(url('proyectos')) ?>">Ver todos los proyectos <?= icon('arrow') ?></a>
    </div>
    <div class="grid-cards">
      <?php foreach ($projects as $pr): ?>
        <article class="card">
          <a class="card__media" href="<?= e(project_url($pr)) ?>" tabindex="-1" aria-hidden="true">
            <img src="<?= e(upload_url($pr['cover'])) ?>" alt="" loading="lazy">
            <?php if ($pr['status']): ?><span class="badge badge--venta"><?= e($pr['status']) ?></span><?php endif; ?>
          </a>
          <div class="card__body">
            <p class="card__type"><?= e($pr['location_text']) ?></p>
            <h3 class="card__title"><a href="<?= e(project_url($pr)) ?>"><?= e($pr['name']) ?></a></h3>
            <?php if ($pr['price_from']): ?><p class="card__price"><small>Desde</small> <?= e(money($pr['price_from'])) ?></p><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container contact-band">
    <div>
      <p class="eyebrow">Contacto</p>
      <h2 class="section__title">Cuéntanos qué estás buscando</h2>
      <p>Te respondemos en menos de 24 horas hábiles. Si es urgente, escríbenos por WhatsApp.</p>
      <ul class="contact-list">
        <li><?= icon('phone') ?> <a href="tel:+57<?= e(preg_replace('/\D/', '', site('phone'))) ?>"><?= e(phone_display(site('phone'))) ?></a></li>
        <li><?= icon('mail') ?> <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a></li>
        <li><?= icon('pin') ?> <?= e(site('address')) ?>, <?= e(site('city')) ?></li>
      </ul>
    </div>
    <?php view('contact_form'); ?>
  </div>
</section>

<?php view('footer'); ?>
