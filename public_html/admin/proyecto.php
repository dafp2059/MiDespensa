<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$id = (int) input('id');
$pr = $id ? query('SELECT * FROM projects WHERE id = ?', [$id])->fetch() : null;
if ($id && !$pr) {
    redirect('admin/proyectos.php');
}
$errors = [];

if (is_post()) {
    csrf_check();
    if (input('accion') === 'eliminar' && $pr) {
        delete_owner_images('project', $id);
        query('DELETE FROM projects WHERE id = ?', [$id]);
        flash('success', 'Proyecto eliminado.');
        redirect('admin/proyectos.php');
    }
    $data = [
        'name' => mb_substr(input('name'), 0, 190),
        'location_text' => input('location_text') ?: null,
        'status' => input('status') ?: null,
        'price_from' => input('price_from') === '' ? null : parse_money(input('price_from')),
        'description' => input('description') ?: null,
        'sort_order' => (int) input('sort_order'),
        'published' => input('published') === '1' ? 1 : 0,
    ];
    if ($data['name'] === '') $errors[] = 'El nombre es obligatorio.';
    if (!$errors) {
        if ($pr) {
            query('UPDATE projects SET name = ?, location_text = ?, status = ?, price_from = ?, description = ?, sort_order = ?, published = ? WHERE id = ?',
                [...array_values($data), $id]);
        } else {
            query('INSERT INTO projects (slug, name, location_text, status, price_from, description, sort_order, published) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [unique_slug('projects', $data['name']), ...array_values($data)]);
            $id = (int) db()->lastInsertId();
        }
        $added = sync_images('project', $id);
        flash('success', 'Proyecto guardado.' . ($added ? " Se agregaron {$added} foto(s)." : ''));
        redirect('admin/proyecto.php?id=' . $id);
    }
    $pr = array_merge($pr ?? [], $data);
}

$v = fn(string $key) => e($pr[$key] ?? '');
$images = $id ? images_for('project', $id) : [];
$title = $pr && $id ? 'Editar proyecto' : 'Nuevo proyecto';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1><?= e($title) ?></h1></div>
<?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>

<form class="form admin-form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <fieldset class="admin-card">
    <legend>Datos del proyecto</legend>
    <div class="field"><label for="name">Nombre *</label><input id="name" name="name" required value="<?= $v('name') ?>"></div>
    <div class="form__row form__row--4">
      <div class="field"><label for="location_text">Ubicación</label><input id="location_text" name="location_text" placeholder="Ej.: Rionegro, Antioquia" value="<?= $v('location_text') ?>"></div>
      <div class="field"><label for="status">Etapa</label><input id="status" name="status" placeholder="Ej.: En construcción" value="<?= $v('status') ?>"></div>
      <div class="field"><label for="price_from">Precio desde (COP)</label><input id="price_from" name="price_from" inputmode="numeric" data-money value="<?= !empty($pr['price_from']) ? e(money($pr['price_from'], false)) : '' ?>"></div>
      <div class="field"><label for="sort_order">Orden</label><input id="sort_order" name="sort_order" type="number" value="<?= $v('sort_order') ?: 0 ?>"></div>
    </div>
    <div class="field"><label for="description">Descripción</label><textarea id="description" name="description" rows="7"><?= $v('description') ?></textarea></div>
    <label class="check"><input type="checkbox" name="published" value="1" <?= ($pr['published'] ?? 1) ? 'checked' : '' ?>> <span>Publicado en la web</span></label>
  </fieldset>
  <?php view('admin_images', ['images' => $images]); ?>
  <div class="admin-actions">
    <button class="btn" type="submit">Guardar</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/proyectos.php')) ?>">Volver</a>
    <?php if ($id): ?><button class="btn btn--danger" type="submit" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar este proyecto y sus fotos?">Eliminar</button><?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
