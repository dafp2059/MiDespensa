<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$where = ['1 = 1'];
$params = [];
if (($q = input('q')) !== '') {
    $where[] = '(p.title LIKE ? OR p.code = ? OR p.neighborhood LIKE ?)';
    array_push($params, "%{$q}%", $q, "%{$q}%");
}
if (isset(STATUSES[input('estado')])) {
    $where[] = 'p.status = ?';
    $params[] = input('estado');
}
if (input('sin_fotos') === '1') {
    $where[] = "NOT EXISTS (SELECT 1 FROM images i WHERE i.owner_type = 'property' AND i.owner_id = p.id)";
}
$properties = query(property_select() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.updated_at DESC', $params)->fetchAll();

$title = 'Inmuebles';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Inmuebles</h1>
  <a class="btn" href="<?= e(url('admin/inmueble.php')) ?>">+ Nuevo inmueble</a>
</div>

<form class="admin-filters" method="get">
  <div class="field"><label for="q">Buscar</label><input id="q" name="q" placeholder="Título, código o barrio" value="<?= e($q) ?>"></div>
  <div class="field">
    <label for="estado">Estado</label>
    <select id="estado" name="estado">
      <option value="">Todos</option>
      <?php foreach (STATUSES as $key => $label): ?><option value="<?= e($key) ?>" <?= input('estado') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--ghost" type="submit">Filtrar</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Foto</th><th>Inmueble</th><th>Precio</th><th>Estado</th><th>Web</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($properties as $p): ?>
      <tr>
        <td><img class="table__thumb" src="<?= e(upload_url($p['cover'])) ?>" alt=""></td>
        <td>
          <strong><?= e($p['title']) ?></strong><?= $p['featured'] ? ' <span class="pill">Destacado</span>' : '' ?><br>
          <span class="muted"><?= e(($p['code'] ? $p['code'] . ' · ' : '') . TYPES[$p['type']] . ' en ' . $p['operation'] . (property_place($p) ? ' · ' . property_place($p) : '')) ?></span>
        </td>
        <td><?= e(money($p['price'], false)) ?></td>
        <td><span class="status status--<?= e($p['status']) ?>"><?= e(STATUSES[$p['status']]) ?></span></td>
        <td><?= $p['published'] ? '<a href="' . e(property_url($p)) . '" target="_blank">Ver</a>' : '<span class="muted">Oculto</span>' ?></td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/inmueble.php?id=' . $p['id'])) ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$properties): ?><tr><td colspan="6" class="muted">No hay inmuebles con esos filtros.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
