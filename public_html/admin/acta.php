<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = require_can('actas');

$id = (int) input('id');

if (is_post() && input('accion') === 'crear') {
    csrf_check();
    $contract = contract_full((int) input('contrato'));
    $type = isset(INVENTORY_TYPES[input('tipo')]) ? input('tipo') : 'entrega';
    if (!$contract) {
        redirect('admin/contratos.php');
    }
    $base = $type === 'recepcion'
        ? query("SELECT id FROM inventories WHERE contract_id = ? AND type = 'entrega' ORDER BY inspected_at DESC, id DESC LIMIT 1", [$contract['id']])->fetchColumn()
        : null;
    query('INSERT INTO inventories (contract_id, type, based_on_id, inspected_at, created_by) VALUES (?, ?, ?, CURDATE(), ?)',
        [$contract['id'], $type, $base ?: null, $user['id']]);
    $id = (int) db()->lastInsertId();
    $order = 0;
    if ($base) {
        // Recepción: se parte de lo que se anotó en la entrega, para comparar.
        foreach (query('SELECT * FROM inventory_items WHERE inventory_id = ? ORDER BY sort_order, id', [$base])->fetchAll() as $item) {
            query('INSERT INTO inventory_items (inventory_id, area, item, state, prev_state, prev_notes, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$id, $item['area'], $item['item'], $item['state'], $item['state'], $item['notes'], $order++]);
        }
        $prev = query('SELECT keys_count FROM inventories WHERE id = ?', [$base])->fetch();
        query('UPDATE inventories SET keys_count = ? WHERE id = ?', [$prev['keys_count'] ?? null, $id]);
    } else {
        foreach (default_inventory() as $area => $items) {
            foreach ($items as $item) {
                query('INSERT INTO inventory_items (inventory_id, area, item, sort_order) VALUES (?, ?, ?, ?)', [$id, $area, $item, $order++]);
            }
        }
    }
    flash('success', INVENTORY_TYPES[$type] . ' creada. Recorre el inmueble y ve marcando el estado de cada cosa.');
    redirect('admin/acta.php?id=' . $id);
}

$inv = $id ? inventory_full($id) : null;
if (!$inv || !$inv['contract']) {
    redirect('admin/contratos.php');
}

if (is_post()) {
    csrf_check();
    if (input('accion') === 'eliminar' && can('eliminar')) {
        foreach ($inv['photos'] as $photo) delete_image_file($photo['filename']);
        query('DELETE FROM inventories WHERE id = ?', [$id]);
        flash('success', 'Acta eliminada.');
        redirect('admin/contrato.php?id=' . $inv['contract_id']);
    }

    query('UPDATE inventories SET inspected_at = ?, meter_water = ?, meter_power = ?, meter_gas = ?, keys_count = ?, notes = ? WHERE id = ?', [
        preg_match('/^\d{4}-\d{2}-\d{2}$/', input('inspected_at')) ? input('inspected_at') : $inv['inspected_at'],
        mb_substr(input('meter_water'), 0, 40) ?: null, mb_substr(input('meter_power'), 0, 40) ?: null, mb_substr(input('meter_gas'), 0, 40) ?: null,
        mb_substr(input('keys_count'), 0, 80) ?: null, input('notes') ?: null, $id,
    ]);
    foreach ($inv['items'] as $item) {
        $state = $_POST['state'][$item['id']] ?? $item['state'];
        query('UPDATE inventory_items SET state = ?, notes = ? WHERE id = ?', [
            isset(INVENTORY_STATES[$state]) ? $state : $item['state'], mb_substr(trim((string) ($_POST['notes'][$item['id']] ?? '')), 0, 500) ?: null, $item['id'],
        ]);
    }
    // Elementos y espacios nuevos
    $order = count($inv['items']);
    foreach ((array) ($_POST['new_item'] ?? []) as $i => $name) {
        $area = trim((string) ($_POST['new_area'][$i] ?? ''));
        if (trim((string) $name) === '' || $area === '') continue;
        query('INSERT INTO inventory_items (inventory_id, area, item, state, notes, sort_order) VALUES (?, ?, ?, ?, ?, ?)', [
            $id, mb_substr($area, 0, 80), mb_substr(trim((string) $name), 0, 120),
            isset(INVENTORY_STATES[$_POST['new_state'][$i] ?? '']) ? $_POST['new_state'][$i] : 'bueno',
            mb_substr(trim((string) ($_POST['new_notes'][$i] ?? '')), 0, 500) ?: null, $order++,
        ]);
    }
    // Fotos por espacio
    $added = 0;
    foreach ((array) ($_POST['photo_area'] ?? []) as $i => $area) {
        foreach (save_uploaded_images('fotos_' . (int) $i) as $filename) {
            query('INSERT INTO inventory_photos (inventory_id, area, filename) VALUES (?, ?, ?)', [$id, mb_substr((string) $area, 0, 80), $filename]);
            $added++;
        }
    }
    foreach ((array) ($_POST['delete_photo'] ?? []) as $photoId) {
        $photo = query('SELECT * FROM inventory_photos WHERE id = ? AND inventory_id = ?', [(int) $photoId, $id])->fetch();
        if ($photo) {
            delete_image_file($photo['filename']);
            query('DELETE FROM inventory_photos WHERE id = ?', [$photo['id']]);
        }
    }
    flash('success', 'Acta guardada.' . ($added ? " Se agregaron {$added} foto(s)." : ''));
    redirect('admin/acta.php?id=' . $id . (input('ir') ? '#' . preg_replace('/[^\w-]/', '', input('ir')) : ''));
}

$c = $inv['contract'];
$areas = group_by_area($inv['items']);
$photos = group_by_area($inv['photos']);
$isReception = $inv['type'] === 'recepcion';
$title = INVENTORY_TYPES[$inv['type']];
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><a href="<?= e(url('admin/contrato.php?id=' . $c['id'])) ?>"><?= e($c['code']) ?></a> · <?= e($c['property_title']) ?> · <?= e($c['client_name']) ?></p>
  </div>
  <a class="btn" href="<?= e(url('admin/acta-imprimir.php?id=' . $id)) ?>" target="_blank">Imprimir / PDF</a>
</div>
<p class="admin-help">Toca cada espacio para abrirlo. Marca el estado, escribe lo que veas y toma fotos con el celular. <?= $isReception ? 'Al lado de cada elemento ves cómo se entregó.' : '' ?> Guarda cada tanto: no se pierde nada.</p>

<form class="form admin-form inventory" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <fieldset class="admin-card">
    <legend>Datos generales</legend>
    <div class="form__row form__row--4">
      <div class="field"><label for="inspected_at">Fecha</label><input id="inspected_at" type="date" name="inspected_at" value="<?= e($inv['inspected_at']) ?>"></div>
      <div class="field"><label for="keys_count">Llaves entregadas</label><input id="keys_count" name="keys_count" placeholder="Ej.: 3 llaves y 1 control" value="<?= e($inv['keys_count']) ?>"></div>
      <div class="field"><label for="meter_water">Contador de agua</label><input id="meter_water" name="meter_water" inputmode="decimal" value="<?= e($inv['meter_water']) ?>"></div>
      <div class="field"><label for="meter_power">Contador de energía</label><input id="meter_power" name="meter_power" inputmode="decimal" value="<?= e($inv['meter_power']) ?>"></div>
      <div class="field"><label for="meter_gas">Contador de gas</label><input id="meter_gas" name="meter_gas" inputmode="decimal" value="<?= e($inv['meter_gas']) ?>"></div>
    </div>
  </fieldset>

  <?php $i = 0; foreach ($areas as $area => $items): $anchor = 'area-' . $i; ?>
    <details class="admin-card inventory__area" id="<?= $anchor ?>"<?= $i === 0 ? ' open' : '' ?>>
      <summary><?= e($area) ?> <span class="muted"><?= count(array_filter($items, fn($it) => in_array($it['state'], ['regular', 'malo'], true))) ?: '' ?></span></summary>
      <?php foreach ($items as $item): ?>
        <div class="inventory__item">
          <div class="inventory__name">
            <strong><?= e($item['item']) ?></strong>
            <?php if ($isReception && $item['prev_state']): ?><span class="muted">Entrega: <?= e(INVENTORY_STATES[$item['prev_state']] ?? $item['prev_state']) ?><?= $item['prev_notes'] ? ' · ' . e($item['prev_notes']) : '' ?></span><?php endif; ?>
          </div>
          <div class="state-pick" role="radiogroup" aria-label="Estado de <?= e($item['item']) ?>">
            <?php foreach (INVENTORY_STATES as $k => $l): ?>
              <label class="state-pick__opt state-pick__opt--<?= $k ?>"><input type="radio" name="state[<?= (int) $item['id'] ?>]" value="<?= $k ?>" <?= $item['state'] === $k ? 'checked' : '' ?>><span><?= e($l) ?></span></label>
            <?php endforeach; ?>
          </div>
          <input class="inventory__notes" name="notes[<?= (int) $item['id'] ?>]" value="<?= e($item['notes']) ?>" placeholder="Observaciones" aria-label="Observaciones de <?= e($item['item']) ?>">
        </div>
      <?php endforeach; ?>

      <div class="inventory__photos">
        <?php foreach ($photos[$area] ?? [] as $ph): ?>
          <label class="inventory__photo"><img src="<?= e(upload_url($ph['filename'])) ?>" alt="Foto de <?= e($area) ?>"><span><input type="checkbox" name="delete_photo[]" value="<?= (int) $ph['id'] ?>"> Quitar</span></label>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="photo_area[<?= $i ?>]" value="<?= e($area) ?>">
      <label class="btn btn--ghost btn--small file-btn"><?= icon('camera') ?> Tomar o subir fotos<input type="file" name="fotos_<?= $i ?>[]" accept="image/*" capture="environment" multiple></label>
      <div><button class="btn btn--small" type="submit" name="ir" value="<?= $anchor ?>">Guardar</button></div>
    </details>
  <?php $i++; endforeach; ?>

  <fieldset class="admin-card" data-repeater>
    <legend>Agregar elementos o espacios</legend>
    <p class="muted">Escribe el espacio (uno de los de arriba o uno nuevo, por ejemplo "Balcón") y el elemento.</p>
    <div data-rows></div>
    <template>
      <div class="repeater__row">
        <label class="field"><span class="field__label">Espacio</span><input name="new_area[]" list="areas"></label>
        <label class="field"><span class="field__label">Elemento</span><input name="new_item[]"></label>
        <label class="field field--small"><span class="field__label">Estado</span><select name="new_state[]"><?php foreach (INVENTORY_STATES as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></label>
        <label class="field"><span class="field__label">Observaciones</span><input name="new_notes[]"></label>
        <div class="repeater__tools"><button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button></div>
      </div>
    </template>
    <div><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar elemento</button></div>
    <datalist id="areas"><?php foreach (array_keys($areas) as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Observaciones generales</legend>
    <textarea name="notes" rows="4" aria-label="Observaciones generales" placeholder="Ej.: se entrega recién pintado; el arrendatario se compromete a…"><?= e($inv['notes']) ?></textarea>
  </fieldset>

  <div class="admin-actions">
    <button class="btn" type="submit">Guardar acta</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/contrato.php?id=' . $c['id'])) ?>">Volver al contrato</a>
    <?php if (can('eliminar')): ?><button class="btn btn--danger" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar esta acta y sus fotos?">Eliminar</button><?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
