<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('inmuebles.ver');

$where = ['1 = 1'];
$params = [];
if (($q = input('q')) !== '') {
    $where[] = '(p.title LIKE ? OR p.neighborhood LIKE ?)';
    array_push($params, "%{$q}%", "%{$q}%");
}
if (($code = preg_replace('/\D/', '', input('codigo'))) !== '') {
    $where[] = 'p.code = ?';
    $params[] = $code;
}
if (isset(OPERATIONS[input('operacion')])) {
    $where[] = 'p.operation = ?';
    $params[] = input('operacion');
}
if ((int) input('asesor')) {
    $where[] = 'p.agent_user_id = ?';
    $params[] = (int) input('asesor');
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
  <?php if (can('inmuebles.editar')): ?><a class="btn" href="<?= e(url('admin/inmueble.php')) ?>">+ Nuevo inmueble</a><?php endif; ?>
</div>

<form class="admin-filters" method="get">
  <div class="field field--small"><label for="codigo">Código</label><input id="codigo" name="codigo" inputmode="numeric" pattern="[0-9]*" placeholder="Ej.: 105" value="<?= e(input('codigo')) ?>"></div>
  <div class="field"><label for="q">Título o barrio</label><input id="q" name="q" value="<?= e($q) ?>"></div>
  <div class="field"><label for="operacion">Operación</label>
    <select id="operacion" name="operacion"><option value="">Todas</option><?php foreach (OPERATIONS as $key => $label): ?><option value="<?= $key ?>" <?= input('operacion') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select>
  </div>
  <div class="field"><label for="asesor">Asesor</label>
    <select id="asesor" name="asesor"><option value="">Todos</option><?php foreach (staff_users() as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) input('asesor') === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
  </div>
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
    <thead><tr><th>Código</th><th>Foto</th><th>Inmueble</th><th>Precio</th><th>Estado</th><th>Web</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($properties as $p): ?>
      <tr>
        <td><strong class="code-badge"><?= e($p['code']) ?></strong></td>
        <td><img class="table__thumb" src="<?= e(upload_url($p['cover'])) ?>" alt=""></td>
        <td>
          <strong><?= e($p['title']) ?></strong><?= $p['featured'] ? ' <span class="pill">Destacado</span>' : '' ?><br>
          <span class="muted"><?= e(TYPES[$p['type']] . ' en ' . $p['operation'] . (property_place($p) ? ' · ' . property_place($p) : '') . ($p['agent_name'] ? ' · ' . $p['agent_name'] : '')) ?></span>
        </td>
        <td><?= e(money($p['price'], false)) ?></td>
        <td><span class="status status--<?= e($p['status']) ?>"><?= e(STATUSES[$p['status']]) ?></span></td>
        <td><?= $p['published'] ? '<a href="' . e(property_url($p)) . '" target="_blank">Ver</a>' : '<span class="muted">Oculto</span>' ?></td>
        <td><?php if (can('inmuebles.editar')): ?><a class="btn btn--small btn--ghost" href="<?= e(url('admin/inmueble.php?id=' . $p['id'])) ?>">Editar</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$properties): ?><tr><td colspan="7" class="muted">No hay inmuebles con esos filtros.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
