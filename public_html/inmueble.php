<?php
require __DIR__ . '/../app/bootstrap.php';

$p = query(property_select() . ' WHERE p.slug = ? AND p.published = 1', [input('slug')])->fetch();
if (!$p) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$images = images_for('property', (int) $p['id']);
$features = array_filter(array_map('trim', preg_split('/[\n,]+/', (string) $p['features'])));
$similar = query(property_select() . " WHERE p.published = 1 AND p.id <> ? AND p.status IN ('disponible','reservado')
    AND (p.type = ? OR p.location_id <=> ?) ORDER BY (p.location_id <=> ?) DESC, p.created_at DESC LIMIT 3",
    [$p['id'], $p['type'], $p['location_id'], $p['location_id']])->fetchAll();

$facts = array_filter([
    ['bed', 'Habitaciones', $p['bedrooms']],
    ['bath', 'Baños', $p['bathrooms']],
    ['area', 'Área construida', $p['area_built'] !== null ? number_es($p['area_built']) . ' m²' : null],
    ['area', 'Área del lote', $p['area_lot'] !== null ? number_es($p['area_lot']) . ' m²' : null],
    ['car', 'Parqueaderos', $p['parking']],
    ['home', 'Estrato', $p['stratum']],
    ['clock', 'Año de construcción', $p['year_built']],
    ['card', 'Administración', $p['admin_fee'] ? money($p['admin_fee']) : null],
], fn($f) => $f[2] !== null && $f[2] !== '');

$place = property_place($p);
$waText = 'Hola, me interesa el inmueble "' . $p['title'] . '" (código ' . ($p['code'] ?: $p['id']) . '): ' . property_url($p);
$mapQuery = $p['latitude'] && $p['longitude'] ? $p['latitude'] . ',' . $p['longitude'] : null;

$page = [
    'active' => 'inmuebles',
    'title' => $p['title'] . ($place ? ' – ' . $place : ''),
    'description' => mb_strimwidth(trim(TYPES[$p['type']] . ' en ' . $p['operation'] . ($place ? ' en ' . $place : '') . '. '
        . preg_replace('/\s+/', ' ', strip_tags((string) $p['description']))), 0, 158, '…'),
    'canonical' => property_url($p),
    'image' => $images ? upload_url($images[0]['filename']) : null,
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'RealEstateListing',
        'name' => $p['title'],
        'url' => property_url($p),
        'datePosted' => date('Y-m-d', strtotime($p['created_at'])),
        'image' => array_map(fn($i) => upload_url($i['filename']), $images),
        'offers' => [
            '@type' => 'Offer',
            'price' => (int) $p['price'],
            'priceCurrency' => 'COP',
            'availability' => is_closed($p) ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
        ],
    ],
];
view('header', compact('page'));
?>

<section class="section section--tight">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Ruta">
      <a href="<?= e(url('')) ?>">Inicio</a> /
      <a href="<?= e(url('inmuebles?operacion=' . $p['operation'])) ?>"><?= e(OPERATIONS[$p['operation']]) ?></a> /
      <span aria-current="page"><?= e($p['title']) ?></span>
    </nav>

    <div class="detail__head">
      <div>
        <div class="detail__badges">
          <span class="badge badge--<?= e($p['operation']) ?>"><?= e(OPERATIONS[$p['operation']]) ?></span>
          <?php if ($p['status'] !== 'disponible'): ?><span class="badge badge--status"><?= e(STATUSES[$p['status']]) ?></span><?php endif; ?>
          <?php if ($p['code']): ?><span class="detail__code">Código <?= e($p['code']) ?></span><?php endif; ?>
        </div>
        <h1 class="detail__title"><?= e($p['title']) ?></h1>
        <?php if ($place): ?><p class="detail__place"><?= icon('pin') ?> <?= e($place) ?></p><?php endif; ?>
      </div>
      <p class="detail__price">
        <?= $p['price'] ? e(money($p['price'])) : 'Precio a convenir' ?>
        <?php if ($p['operation'] === 'arriendo' && $p['price']): ?><small>/ mes</small><?php endif; ?>
      </p>
    </div>

    <div class="gallery" data-gallery>
      <figure class="gallery__main">
        <img src="<?= e(upload_url($images[0]['filename'] ?? null)) ?>" alt="<?= e($p['title']) ?>, foto 1" data-gallery-main>
      </figure>
      <?php if (count($images) > 1): ?>
        <div class="gallery__thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" data-src="<?= e(upload_url($img['filename'])) ?>" data-alt="<?= e($p['title']) ?>, foto <?= $i + 1 ?>" aria-label="Ver foto <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
              <img src="<?= e(upload_url($img['filename'])) ?>" alt="" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="detail">
      <div class="detail__main">
        <?php if ($facts): ?>
          <ul class="facts">
            <?php foreach ($facts as [$ico, $label, $value]): ?>
              <li><?= icon($ico) ?><span><?= e($label) ?></span><strong><?= e($value) ?></strong></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($p['description']): ?>
          <h2 class="detail__subtitle">Descripción</h2>
          <div class="prose"><?= nl2br(e($p['description'])) ?></div>
        <?php endif; ?>

        <?php if ($features): ?>
          <h2 class="detail__subtitle">Características</h2>
          <ul class="features">
            <?php foreach ($features as $f): ?><li><?= icon('check') ?> <?= e($f) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($p['video_url'] && preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})#', $p['video_url'], $m)): ?>
          <h2 class="detail__subtitle">Video</h2>
          <div class="video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($m[1]) ?>" title="Video de <?= e($p['title']) ?>" loading="lazy" allowfullscreen></iframe></div>
        <?php endif; ?>

        <?php if ($mapQuery || $place): ?>
          <h2 class="detail__subtitle">Ubicación</h2>
          <div class="map">
            <iframe src="https://maps.google.com/maps?q=<?= e(rawurlencode($mapQuery ?: $place . ', Antioquia, Colombia')) ?>&amp;z=<?= $mapQuery ? 15 : 13 ?>&amp;output=embed" title="Mapa de la ubicación" loading="lazy"></iframe>
          </div>
          <p class="muted">La ubicación en el mapa es aproximada. La dirección exacta se comparte al agendar la visita.</p>
        <?php endif; ?>
      </div>

      <aside class="detail__aside">
        <div class="panel agent">
          <?php if (is_closed($p)): ?>
            <p class="agent__closed">Este inmueble ya fue <?= $p['status'] === 'vendido' ? 'vendido' : 'arrendado' ?>. Escríbenos y te mostramos opciones parecidas.</p>
          <?php endif; ?>
          <?php if ($p['agent_name']): ?>
            <div class="agent__who">
              <span class="agent__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['agent_name'], 0, 1))) ?></span>
              <div><span class="muted">Asesor a cargo</span><strong><?= e($p['agent_name']) ?></strong></div>
            </div>
          <?php endif; ?>
          <a class="btn btn--block btn--wa" href="<?= e(whatsapp_link($waText)) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Preguntar por WhatsApp</a>
          <a class="btn btn--block btn--ghost" href="tel:+57<?= e(preg_replace('/\D/', '', $p['agent_phone'] ?: site('phone'))) ?>"><?= icon('phone') ?> Llamar</a>
        </div>
        <h2 class="detail__subtitle">Agenda una visita</h2>
        <?php view('contact_form', ['property' => $p]); ?>
      </aside>
    </div>
  </div>
</section>

<?php if ($similar): ?>
<section class="section section--sand">
  <div class="container">
    <h2 class="section__title">Inmuebles similares</h2>
    <div class="grid-cards">
      <?php foreach ($similar as $s) view('property_card', ['p' => $s]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php view('footer'); ?>
