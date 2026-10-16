<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('diseno');
$GLOBALS['needs_editor'] = true;

$id = (int) input('id');
$tpl = $id ? query('SELECT * FROM contract_templates WHERE id = ?', [$id])->fetch() : null;
if ($id && !$tpl) {
    redirect('admin/plantillas.php');
}

if (is_post()) {
    csrf_check();
    if (input('accion') === 'eliminar' && $tpl) {
        query('UPDATE contracts SET template_id = NULL WHERE template_id = ?', [$id]);
        query('DELETE FROM contract_templates WHERE id = ?', [$id]);
        flash('success', 'Plantilla eliminada. Los contratos ya creados conservan su texto.');
        redirect('admin/plantillas.php');
    }
    $data = [
        'name' => mb_substr(input('name'), 0, 150) ?: 'Plantilla sin nombre',
        'type' => isset(CONTRACT_TYPES[input('type')]) ? input('type') : 'arriendo',
        'body' => clean_html((string) ($_POST['body'] ?? '')),
    ];
    if ($tpl) {
        query('UPDATE contract_templates SET name = ?, type = ?, body = ? WHERE id = ?', [...array_values($data), $id]);
    } else {
        query('INSERT INTO contract_templates (name, type, body) VALUES (?, ?, ?)', array_values($data));
        $id = (int) db()->lastInsertId();
    }
    flash('success', 'Plantilla guardada. Los contratos nuevos usarán este texto.');
    redirect('admin/plantilla.php?id=' . $id);
}

$vars = [
    '{{contrato_codigo}}' => 'Código del contrato', '{{fecha_contrato}}' => 'Fecha del contrato', '{{cliente_nombre}}' => 'Arrendatario / comprador',
    '{{cliente_documento}}' => 'Su documento', '{{cliente_telefono}}' => 'Su teléfono', '{{cliente_correo}}' => 'Su correo', '{{cliente_direccion}}' => 'Su dirección',
    '{{propietario_nombre}}' => 'Propietario / vendedor', '{{propietario_documento}}' => 'Su documento', '{{propietario_telefono}}' => 'Su teléfono',
    '{{codeudor_nombre}}' => 'Deudor solidario', '{{codeudor_documento}}' => 'Su documento', '{{codeudor_telefono}}' => 'Su teléfono', '{{codeudor_direccion}}' => 'Su dirección',
    '{{inmueble_tipo}}' => 'casa, apartamento…', '{{inmueble_direccion}}' => 'Dirección del inmueble', '{{inmueble_matricula}}' => 'Matrícula inmobiliaria',
    '{{inmueble_codigo}}' => 'Código del inmueble', '{{valor}}' => 'Canon o precio en números', '{{valor_letras}}' => 'Canon o precio en letras',
    '{{administracion}}' => 'Administración', '{{dia_pago}}' => 'Día de pago', '{{fecha_inicio}}' => 'Inicio del arriendo', '{{fecha_fin}}' => 'Fin del arriendo',
    '{{duracion_meses}}' => 'Meses', '{{fecha_escritura}}' => 'Fecha de escritura', '{{notaria}}' => 'Notaría', '{{plan_pagos}}' => 'Tabla de cuotas (sola en un párrafo)',
    '{{portal}}' => 'Dirección de Mi cuenta', '{{empresa}}' => 'Nombre comercial', '{{razon_social}}' => 'Razón social', '{{nit}}' => 'NIT', '{{ciudad}}' => 'Ciudad',
];
$title = $id ? 'Editar plantilla' : 'Nueva plantilla';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1><?= e($title) ?></h1></div>
<form class="form admin-form" method="post">
  <?= csrf_field() ?>
  <fieldset class="admin-card">
    <legend>Plantilla</legend>
    <div class="form__row">
      <div class="field"><label for="name">Nombre</label><input id="name" name="name" required value="<?= e($tpl['name'] ?? '') ?>"></div>
      <div class="field"><label for="type">Tipo de contrato</label>
        <select id="type" name="type"><?php foreach (CONTRACT_TYPES as $k => $l): ?><option value="<?= $k ?>" <?= ($tpl['type'] ?? input('type')) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <input type="hidden" id="body" name="body" value="<?= e($tpl['body'] ?? '') ?>">
    <div class="editor" data-editor="body"></div>
  </fieldset>
  <details class="admin-card" open>
    <summary>Datos que se llenan solos</summary>
    <p class="muted">Escribe estas palabras tal cual (con las llaves) y al crear un contrato se reemplazan por los datos reales.</p>
    <ul class="vars">
      <?php foreach ($vars as $k => $l): ?><li><code><?= e($k) ?></code> <span class="muted"><?= e($l) ?></span></li><?php endforeach; ?>
    </ul>
  </details>
  <div class="admin-actions">
    <button class="btn" type="submit">Guardar plantilla</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/plantillas.php')) ?>">Volver</a>
    <?php if ($id): ?><button class="btn btn--danger" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar esta plantilla?">Eliminar</button><?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
