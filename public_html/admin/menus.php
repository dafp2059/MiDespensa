<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('diseno');

$menus = [
    'principal' => ['Menú principal', 'Los enlaces de la barra de navegación.', null],
    'superior' => ['Barra superior', 'Enlaces pequeños en la franja oscura de arriba, junto al teléfono y el correo.', null],
    'pie_1' => ['Pie de página: columna 1', '', 'footer.col1_title'],
    'pie_2' => ['Pie de página: columna 2', '', 'footer.col2_title'],
];

if (is_post()) {
    csrf_check();
    foreach ($menus as $key => [, , $titleKey]) {
        $rows = $_POST['menu'][$key] ?? [];
        $items = [];
        foreach ((array) ($rows['label'] ?? []) as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') continue;
            $items[] = [
                'label' => mb_substr($label, 0, 80),
                'url' => mb_substr(trim((string) ($rows['url'][$i] ?? '/')), 0, 255),
                'new_tab' => ($rows['new_tab'][$i] ?? '0') === '1' ? 1 : 0,
            ];
        }
        save_setting('menu.' . $key, json_encode($items, JSON_UNESCAPED_UNICODE));
        if ($titleKey) {
            save_setting($titleKey, mb_substr(input('title_' . $key), 0, 80));
        }
    }
    save_setting('footer.contact_title', mb_substr(input('contact_title'), 0, 80));
    save_setting('footer.copyright', mb_substr(input('copyright'), 0, 300));
    save_setting('footer.team_link', input('team_link') === '1' ? '1' : '0');
    flash('success', 'Menús y pie de página guardados.');
    redirect('admin/menus.php');
}

$row = function (string $menu, array $item = []): void { ?>
  <div class="repeater__row">
    <label class="field"><span class="field__label">Texto</span><input name="menu[<?= $menu ?>][label][]" value="<?= e($item['label'] ?? '') ?>" placeholder="Ej.: Nosotros"></label>
    <label class="field"><span class="field__label">Enlace</span><input name="menu[<?= $menu ?>][url][]" list="enlaces" value="<?= e($item['url'] ?? '') ?>" placeholder="/nosotros o https://…"></label>
    <label class="field field--small"><span class="field__label">Abrir en</span>
      <select name="menu[<?= $menu ?>][new_tab][]"><option value="0">Misma pestaña</option><option value="1" <?= !empty($item['new_tab']) ? 'selected' : '' ?>>Pestaña nueva</option></select></label>
    <div class="repeater__tools">
      <button class="icon-btn" type="button" data-up aria-label="Subir">↑</button>
      <button class="icon-btn" type="button" data-down aria-label="Bajar">↓</button>
      <button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button>
    </div>
  </div>
<?php };

$title = 'Menús';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Menús y pie de página</h1></div>
<p class="admin-help">Escribe el texto y el enlace de cada opción. En "Enlace" puedes elegir una página de la lista o pegar cualquier dirección. Usa las flechas para cambiar el orden. Los cambios se ven al guardar.</p>

<form class="form" method="post">
  <?= csrf_field() ?>
  <?php foreach ($menus as $key => [$label, $help, $titleKey]): ?>
    <fieldset class="admin-card" data-repeater>
      <legend><?= e($label) ?></legend>
      <?php if ($help): ?><p class="muted"><?= e($help) ?></p><?php endif; ?>
      <?php if ($titleKey): ?>
        <div class="field" style="max-width:360px"><label for="title_<?= $key ?>">Título de la columna</label><input id="title_<?= $key ?>" name="title_<?= $key ?>" value="<?= e(setting($titleKey)) ?>"></div>
      <?php endif; ?>
      <div data-rows>
        <?php foreach (setting_list('menu.' . $key) as $item) $row($key, $item); ?>
      </div>
      <template><?php $row($key); ?></template>
      <div><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar enlace</button></div>
    </fieldset>
  <?php endforeach; ?>

  <fieldset class="admin-card">
    <legend>Pie de página</legend>
    <p class="muted">El logo, la frase, las redes y los datos de contacto del pie se cambian en <a href="<?= e(url('admin/ajustes.php')) ?>">Ajustes</a>.</p>
    <div class="form__row">
      <div class="field"><label for="contact_title">Título de la columna de contacto</label><input id="contact_title" name="contact_title" value="<?= e(setting('footer.contact_title')) ?>"></div>
      <div class="field"><label for="copyright">Texto final (derechos)</label><input id="copyright" name="copyright" value="<?= e(setting('footer.copyright')) ?>"><span class="muted">{{año}} y {{empresa}} se reemplazan solos.</span></div>
    </div>
    <label class="check"><input type="checkbox" name="team_link" value="1" <?= setting('footer.team_link') === '1' ? 'checked' : '' ?>> <span>Mostrar el enlace "Acceso equipo" al panel</span></label>
  </fieldset>

  <div class="admin-actions"><button class="btn" type="submit">Guardar menús</button></div>
</form>
<?php view('admin_links'); ?>
<?php view('admin_footer'); ?>
