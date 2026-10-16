<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$id = (int) input('id');
$p = $id ? query('SELECT * FROM properties WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) {
    redirect('admin/inmuebles.php');
}

$nullableInt = fn(string $key) => input($key) === '' ? null : (int) input($key);
$nullableDecimal = fn(string $key) => input($key) === '' ? null : (float) str_replace(',', '.', input($key));
$errors = [];

if (is_post()) {
    csrf_check();

    if (input('accion') === 'eliminar' && $p) {
        delete_owner_images('property', (int) $p['id']);
        query('DELETE FROM properties WHERE id = ?', [$p['id']]);
        flash('success', 'Inmueble eliminado.');
        redirect('admin/inmuebles.php');
    }

    $data = [
        'title' => mb_substr(input('title'), 0, 190),
        'code' => input('code') ?: null,
        'operation' => input('operation'),
        'type' => input('type'),
        'status' => input('status'),
        'price' => parse_money(input('price')),
        'admin_fee' => input('admin_fee') === '' ? null : parse_money(input('admin_fee')),
        'location_id' => $nullableInt('location_id'),
        'neighborhood' => input('neighborhood') ?: null,
        'address' => mb_substr(input('address'), 0, 190) ?: null,
        'registry_number' => mb_substr(input('registry_number'), 0, 60) ?: null,
        'bedrooms' => $nullableInt('bedrooms'),
        'bathrooms' => $nullableInt('bathrooms'),
        'parking' => $nullableInt('parking'),
        'area_built' => $nullableDecimal('area_built'),
        'area_lot' => $nullableDecimal('area_lot'),
        'stratum' => $nullableInt('stratum'),
        'year_built' => $nullableInt('year_built'),
        'description' => input('description') ?: null,
        'features' => input('features') ?: null,
        'latitude' => $nullableDecimal('latitude'),
        'longitude' => $nullableDecimal('longitude'),
        'video_url' => input('video_url') ?: null,
        'agent_name' => input('agent_name') ?: null,
        'agent_phone' => input('agent_phone') ?: null,
        'featured' => input('featured') === '1' ? 1 : 0,
        'published' => input('published') === '1' ? 1 : 0,
    ];
    if ($data['title'] === '') $errors[] = 'El título es obligatorio.';
    if (!isset(OPERATIONS[$data['operation']])) $errors[] = 'Elige venta o arriendo.';
    if (!isset(TYPES[$data['type']])) $errors[] = 'Elige el tipo de inmueble.';
    if (!isset(STATUSES[$data['status']])) $errors[] = 'Elige el estado.';

    if (!$errors) {
        $placeName = '';
        foreach (locations() as $loc) {
            if ((int) $loc['id'] === $data['location_id']) $placeName = $loc['name'];
        }
        $data['slug'] = unique_slug('properties', $data['title'] . ($placeName && !str_contains(mb_strtolower($data['title']), mb_strtolower($placeName)) ? ' ' . $placeName : ''), $id);
        if ($p && $p['slug'] && input('keep_slug') === '1') {
            $data['slug'] = $p['slug'];
        }

        $columns = array_keys($data);
        if ($p) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", $columns));
            query("UPDATE properties SET {$set} WHERE id = ?", [...array_values($data), $id]);
        } else {
            $marks = implode(', ', array_fill(0, count($columns), '?'));
            query('INSERT INTO properties (' . implode(', ', $columns) . ") VALUES ({$marks})", array_values($data));
            $id = (int) db()->lastInsertId();
        }
        if (!$data['code']) {
            query('UPDATE properties SET code = ? WHERE id = ?', [sprintf('ING-%03d', $id), $id]);
        }
        $added = sync_images('property', $id);
        flash('success', 'Inmueble guardado.' . ($added ? " Se agregaron {$added} foto(s)." : ''));
        redirect('admin/inmueble.php?id=' . $id);
    }
    $p = array_merge($p ?? [], $data);
}

$v = fn(string $key) => e($p[$key] ?? '');
$images = $id ? images_for('property', $id) : [];
$title = $p ? 'Editar inmueble' : 'Nuevo inmueble';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1><?= e($title) ?></h1>
  <?php if (!empty($p['slug']) && $id): ?><a href="<?= e(property_url($p)) ?>" target="_blank">Ver en la web</a><?php endif; ?>
</div>
<?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>

<form class="form admin-form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="keep_slug" value="1"><?php endif; ?>

  <fieldset class="admin-card">
    <legend>Datos principales</legend>
    <div class="field"><label for="title">Título *</label><input id="title" name="title" required maxlength="190" placeholder="Ej.: Casa campestre con piscina en El Retiro" value="<?= $v('title') ?>"></div>
    <div class="form__row form__row--4">
      <div class="field"><label for="operation">Operación *</label>
        <select id="operation" name="operation"><?php foreach (OPERATIONS as $k => $l): ?><option value="<?= $k ?>" <?= ($p['operation'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="type">Tipo *</label>
        <select id="type" name="type"><?php foreach (TYPES as $k => $l): ?><option value="<?= $k ?>" <?= ($p['type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="status">Estado *</label>
        <select id="status" name="status"><?php foreach (STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= ($p['status'] ?? 'disponible') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="code">Código</label><input id="code" name="code" placeholder="Automático" value="<?= $v('code') ?>"></div>
    </div>
    <div class="form__row form__row--4">
      <div class="field"><label for="price">Precio (COP)<?= ($p['operation'] ?? '') === 'arriendo' ? ' mensual' : '' ?></label><input id="price" name="price" inputmode="numeric" data-money value="<?= isset($p['price']) && $p['price'] ? e(money($p['price'], false)) : '' ?>"></div>
      <div class="field"><label for="admin_fee">Administración (COP)</label><input id="admin_fee" name="admin_fee" inputmode="numeric" data-money value="<?= !empty($p['admin_fee']) ? e(money($p['admin_fee'], false)) : '' ?>"></div>
      <div class="field"><label for="location_id">Zona</label>
        <select id="location_id" name="location_id"><option value="">Sin zona</option><?php foreach (locations() as $loc): ?><option value="<?= (int) $loc['id'] ?>" <?= (int) ($p['location_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="neighborhood">Barrio o vereda</label><input id="neighborhood" name="neighborhood" value="<?= $v('neighborhood') ?>"></div>
    </div>
    <div class="checks">
      <label class="check"><input type="checkbox" name="published" value="1" <?= ($p['published'] ?? 1) ? 'checked' : '' ?>> <span>Publicado en la web</span></label>
      <label class="check"><input type="checkbox" name="featured" value="1" <?= !empty($p['featured']) ? 'checked' : '' ?>> <span>Destacado en el inicio</span></label>
    </div>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Características</legend>
    <div class="form__row form__row--4">
      <div class="field"><label for="bedrooms">Habitaciones</label><input id="bedrooms" name="bedrooms" type="number" min="0" max="50" value="<?= $v('bedrooms') ?>"></div>
      <div class="field"><label for="bathrooms">Baños</label><input id="bathrooms" name="bathrooms" type="number" min="0" max="50" value="<?= $v('bathrooms') ?>"></div>
      <div class="field"><label for="parking">Parqueaderos</label><input id="parking" name="parking" type="number" min="0" max="50" value="<?= $v('parking') ?>"></div>
      <div class="field"><label for="stratum">Estrato</label><input id="stratum" name="stratum" type="number" min="1" max="6" value="<?= $v('stratum') ?>"></div>
    </div>
    <div class="form__row form__row--4">
      <div class="field"><label for="area_built">Área construida (m²)</label><input id="area_built" name="area_built" inputmode="decimal" value="<?= $v('area_built') ?>"></div>
      <div class="field"><label for="area_lot">Área del lote (m²)</label><input id="area_lot" name="area_lot" inputmode="decimal" value="<?= $v('area_lot') ?>"></div>
      <div class="field"><label for="year_built">Año de construcción</label><input id="year_built" name="year_built" type="number" min="1900" max="2100" value="<?= $v('year_built') ?>"></div>
      <div class="field"><label for="video_url">Video de YouTube</label><input id="video_url" name="video_url" type="url" placeholder="https://youtu.be/..." value="<?= $v('video_url') ?>"></div>
    </div>
    <div class="field"><label for="description">Descripción</label><textarea id="description" name="description" rows="7" placeholder="Describe el inmueble: espacios, acabados, vista, cercanía a vías, colegios, comercio…"><?= $v('description') ?></textarea></div>
    <div class="field"><label for="features">Lista de características (una por línea)</label><textarea id="features" name="features" rows="5" placeholder="Piscina&#10;Chimenea&#10;Zona BBQ&#10;Portería 24 horas"><?= $v('features') ?></textarea></div>
  </fieldset>

  <?php view('admin_images', ['images' => $images]); ?>

  <fieldset class="admin-card">
    <legend>Ubicación en el mapa y asesor</legend>
    <p class="muted">Para obtener las coordenadas: en Google Maps haz clic derecho sobre el inmueble y copia los dos números.</p>
    <div class="form__row form__row--4">
      <div class="field"><label for="latitude">Latitud</label><input id="latitude" name="latitude" inputmode="decimal" placeholder="6.0605" value="<?= $v('latitude') ?>"></div>
      <div class="field"><label for="longitude">Longitud</label><input id="longitude" name="longitude" inputmode="decimal" placeholder="-75.5020" value="<?= $v('longitude') ?>"></div>
      <div class="field"><label for="agent_name">Asesor a cargo</label><input id="agent_name" name="agent_name" value="<?= $v('agent_name') ?>"></div>
      <div class="field"><label for="agent_phone">Teléfono del asesor</label><input id="agent_phone" name="agent_phone" type="tel" value="<?= $v('agent_phone') ?>"></div>
    </div>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Datos para contratos (no se muestran en la web)</legend>
    <div class="form__row">
      <div class="field"><label for="address">Dirección exacta</label><input id="address" name="address" placeholder="Ej.: Calle 10 # 43-21, apto 502" value="<?= $v('address') ?>"></div>
      <div class="field"><label for="registry_number">Matrícula inmobiliaria</label><input id="registry_number" name="registry_number" placeholder="Ej.: 001-1234567" value="<?= $v('registry_number') ?>"></div>
    </div>
  </fieldset>

  <div class="admin-actions">
    <button class="btn" type="submit">Guardar</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/inmuebles.php')) ?>">Volver</a>
    <?php if ($id): ?>
      <button class="btn btn--danger" type="submit" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar este inmueble y todas sus fotos? No se puede deshacer.">Eliminar</button>
    <?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
