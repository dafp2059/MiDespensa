<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();
$GLOBALS['needs_editor'] = true;

$id = (int) input('id');
$pg = $id ? query('SELECT * FROM pages WHERE id = ?', [$id])->fetch() : null;
if ($id && !$pg) {
    redirect('admin/paginas.php');
}
$errors = [];

if (is_post()) {
    csrf_check();
    if (input('accion') === 'eliminar' && $pg && !$pg['locked']) {
        if ($pg['image']) delete_image_file($pg['image']);
        query('DELETE FROM pages WHERE id = ?', [$id]);
        flash('success', 'Página eliminada. Si estaba en algún menú, quítala en Menús.');
        redirect('admin/paginas.php');
    }

    $data = [
        'title' => mb_substr(input('title'), 0, 190),
        'eyebrow' => mb_substr(input('eyebrow'), 0, 120) ?: null,
        'intro' => input('intro') ?: null,
        'content' => clean_html((string) ($_POST['content'] ?? '')),
        'meta_description' => mb_substr(input('meta_description'), 0, 300) ?: null,
        'form' => isset(PAGE_FORMS[input('form')]) ? input('form') : 'none',
        'form_reason' => isset(CONTACT_REASONS[input('form_reason')]) ? input('form_reason') : null,
        'form_title' => mb_substr(input('form_title'), 0, 190) ?: null,
        'published' => input('published') === '1' || ($pg['locked'] ?? 0) ? 1 : 0,
    ];
    if ($data['title'] === '') $errors[] = 'El título es obligatorio.';

    $slug = $pg && $pg['locked'] ? $pg['slug'] : slugify(input('slug') ?: $data['title']);
    if (!($pg && $pg['locked'])) {
        if (in_array($slug, RESERVED_SLUGS, true) || is_file(PUBLIC_DIR . '/' . $slug . '.php')) {
            $errors[] = 'La dirección "/' . $slug . '" ya la usa la web. Elige otra.';
        } elseif (query('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $id])->fetch()) {
            $errors[] = 'Ya existe otra página con la dirección "/' . $slug . '".';
        }
    }

    if (!$errors) {
        $data['slug'] = $slug;
        $columns = array_keys($data);
        if ($pg) {
            query('UPDATE pages SET ' . implode(', ', array_map(fn($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            query('INSERT INTO pages (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $id = (int) db()->lastInsertId();
        }
        $current = query('SELECT image FROM pages WHERE id = ?', [$id])->fetchColumn();
        if (input('quitar_imagen') === '1' && $current) {
            delete_image_file($current);
            query('UPDATE pages SET image = NULL WHERE id = ?', [$id]);
        }
        if (!empty($_FILES['image']['name'])) {
            $_FILES['__page'] = array_map(fn($v) => [$v], $_FILES['image']);
            $saved = save_uploaded_images('__page');
            if ($saved) {
                if ($current) delete_image_file($current);
                query('UPDATE pages SET image = ? WHERE id = ?', [$saved[0], $id]);
            } else {
                flash('error', 'La imagen debe ser JPG, PNG o WEBP.');
            }
        }
        flash('success', 'Página guardada.');
        redirect('admin/pagina.php?id=' . $id);
    }
    $pg = array_merge($pg ?? [], $data, ['slug' => $slug]);
}

$v = fn(string $k) => e($pg[$k] ?? '');
$locked = !empty($pg['locked']);
$title = $id ? 'Editar página' : 'Nueva página';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1><?= e($title) ?></h1>
  <?php if ($id): ?><a href="<?= e(url($pg['slug'])) ?>" target="_blank">Ver en la web</a><?php endif; ?>
</div>
<?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>

<form class="form admin-form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <fieldset class="admin-card">
    <legend>Encabezado</legend>
    <div class="form__row">
      <div class="field"><label for="title">Título *</label><input id="title" name="title" required value="<?= $v('title') ?>"></div>
      <div class="field"><label for="eyebrow">Texto pequeño sobre el título</label><input id="eyebrow" name="eyebrow" value="<?= $v('eyebrow') ?>"></div>
    </div>
    <div class="field"><label for="intro">Introducción</label><textarea id="intro" name="intro" rows="2"><?= $v('intro') ?></textarea></div>
    <div class="form__row">
      <div class="field">
        <label for="image">Foto de fondo del encabezado (opcional)</label>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($pg['image'])): ?>
          <img class="table__thumb" src="<?= e(upload_url($pg['image'])) ?>" alt="Foto actual">
          <label class="check"><input type="checkbox" name="quitar_imagen" value="1"> <span>Quitar la foto</span></label>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="slug">Dirección</label>
        <input id="slug" name="slug" value="<?= $v('slug') ?>" placeholder="se crea con el título" <?= $locked ? 'disabled' : '' ?>>
        <span class="muted"><?= e(url('') . ($pg['slug'] ?? '…')) ?></span>
      </div>
    </div>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Contenido</legend>
    <p class="muted">Puedes usar {{empresa}}, {{razon_social}}, {{nit}}, {{direccion}}, {{ciudad}}, {{telefono}}, {{correo}} y {{horario}}: se reemplazan solos con los datos de Ajustes.</p>
    <input type="hidden" id="content" name="content" value="<?= $v('content') ?>">
    <div class="editor" data-editor="content"></div>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Formulario y Google</legend>
    <div class="form__row">
      <div class="field"><label for="form">Formulario de contacto</label>
        <select id="form" name="form"><?php foreach (PAGE_FORMS as $k => $l): ?><option value="<?= $k ?>" <?= ($pg['form'] ?? 'none') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="form_reason">Motivo marcado por defecto</label>
        <select id="form_reason" name="form_reason"><option value="">Ninguno</option><?php foreach (CONTACT_REASONS as $k => $l): ?><option value="<?= $k ?>" <?= ($pg['form_reason'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="form_title">Título sobre el formulario</label><input id="form_title" name="form_title" value="<?= $v('form_title') ?>"></div>
    </div>
    <div class="field"><label for="meta_description">Descripción para Google</label><textarea id="meta_description" name="meta_description" rows="2" maxlength="300"><?= $v('meta_description') ?></textarea></div>
    <?php if (!$locked): ?>
      <label class="check"><input type="checkbox" name="published" value="1" <?= ($pg['published'] ?? 1) ? 'checked' : '' ?>> <span>Publicada</span></label>
    <?php endif; ?>
  </fieldset>

  <div class="admin-actions">
    <button class="btn" type="submit">Guardar página</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/paginas.php')) ?>">Volver</a>
    <?php if ($id && !$locked): ?><button class="btn btn--danger" type="submit" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar esta página?">Eliminar</button><?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
