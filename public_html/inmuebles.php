<?php
require __DIR__ . '/../app/bootstrap.php';

$where = ['p.published = 1'];
$params = [];

$operation = input('operacion');
if (isset(OPERATIONS[$operation])) {
    $where[] = 'p.operation = ?';
    $params[] = $operation;
}
$type = input('tipo');
if (isset(TYPES[$type])) {
    $where[] = 'p.type = ?';
    $params[] = $type;
}
$location = input('ubicacion');
if ($location !== '') {
    $where[] = 'l.slug = ?';
    $params[] = $location;
}
if ($min = parse_money(input('precio_min'))) {
    $where[] = 'p.price >= ?';
    $params[] = $min;
}
if ($max = parse_money(input('precio_max'))) {
    $where[] = 'p.price <= ?';
    $params[] = $max;
}
if ($rooms = (int) input('habitaciones')) {
    $where[] = 'p.bedrooms >= ?';
    $params[] = $rooms;
}
$q = input('q');
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.code = ? OR p.neighborhood LIKE ? OR p.description LIKE ?)';
    array_push($params, "%{$q}%", $q, "%{$q}%", "%{$q}%");
}
$showClosed = input('cerrados') === '1';
if (!$showClosed) {
    $where[] = "p.status IN ('disponible','reservado')";
}

$orders = [
    'recientes' => 'p.created_at DESC',
    'precio_asc' => 'p.price ASC',
    'precio_desc' => 'p.price DESC',
];
$sort = isset($orders[input('orden')]) ? input('orden') : 'recientes';

$whereSql = ' WHERE ' . implode(' AND ', $where);
$total = (int) query('SELECT COUNT(*) FROM properties p LEFT JOIN locations l ON l.id = p.location_id' . $whereSql, $params)->fetchColumn();

$perPage = 12;
$pages = max(1, (int) ceil($total / $perPage));
$current = min($pages, max(1, (int) input('pagina', '1')));
$offset = ($current - 1) * $perPage;

$properties = query(property_select() . $whereSql . " ORDER BY (p.status = 'disponible') DESC, {$orders[$sort]} LIMIT {$perPage} OFFSET {$offset}", $params)->fetchAll();

// Título según los filtros: "Apartamentos en arriendo en Medellín"
$heading = isset(TYPES_PLURAL[$type]) ? TYPES_PLURAL[$type] : 'Inmuebles';
if ($operation) {
    $heading .= $operation === 'venta' ? ' en venta' : ' en arriendo';
}
foreach (locations() as $loc) {
    if ($loc['slug'] === $location) {
        $heading .= ' en ' . $loc['name'];
    }
}

$page = [
    'active' => 'inmuebles',
    'title' => $heading,
    'description' => $heading . '. Encuentra tu próximo inmueble con ' . site('name') . '.',
    'noindex' => $q !== '' || $current > 1,
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Inmuebles</p>
    <h1 class="page-head__title"><?= e($heading) ?></h1>
  </div>
</section>

<section class="section section--tight">
  <div class="container listing">
    <aside class="listing__filters">
      <?php view('search_form'); ?>
    </aside>

    <div class="listing__results">
      <div class="listing__bar">
        <p><strong><?= $total ?></strong> <?= $total === 1 ? 'inmueble encontrado' : 'inmuebles encontrados' ?></p>
        <form method="get" class="listing__sort">
          <?php foreach ($_GET as $key => $value): if ($key === 'orden' || $key === 'pagina' || !is_string($value)) continue; ?>
            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
          <?php endforeach; ?>
          <label for="orden">Ordenar por</label>
          <select id="orden" name="orden" data-autosubmit>
            <option value="recientes" <?= $sort === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
            <option value="precio_asc" <?= $sort === 'precio_asc' ? 'selected' : '' ?>>Menor precio</option>
            <option value="precio_desc" <?= $sort === 'precio_desc' ? 'selected' : '' ?>>Mayor precio</option>
          </select>
          <noscript><button class="btn btn--small" type="submit">Aplicar</button></noscript>
        </form>
      </div>

      <?php if ($properties): ?>
        <div class="grid-cards grid-cards--two">
          <?php foreach ($properties as $p) view('property_card', ['p' => $p]); ?>
        </div>
      <?php else: ?>
        <div class="empty">
          <p><strong>No encontramos inmuebles con esos filtros.</strong></p>
          <p>Prueba con otra zona o con un precio mayor. También puedes contarnos qué buscas y te avisamos cuando llegue algo.</p>
          <a class="btn" href="<?= e(whatsapp_link('Hola, estoy buscando un inmueble y no encontré lo que necesito en la web.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Avísame por WhatsApp</a>
        </div>
      <?php endif; ?>

      <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Páginas">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="<?= e(query_with(['pagina' => $i])) ?>"<?= $i === $current ? ' aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>

      <p class="listing__closed">
        <?php if ($showClosed): ?>
          <a href="<?= e(query_with(['cerrados' => null, 'pagina' => null])) ?>">Ocultar inmuebles vendidos y arrendados</a>
        <?php else: ?>
          <a href="<?= e(query_with(['cerrados' => '1', 'pagina' => null])) ?>">Ver también los inmuebles que ya vendimos y arrendamos</a>
        <?php endif; ?>
      </p>
    </div>
  </div>
</section>

<?php view('footer'); ?>
