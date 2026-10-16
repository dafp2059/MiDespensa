<?php
require __DIR__ . '/../../app/bootstrap.php';
$me = require_can('usuarios');

/** Cuántos administradores activos quedarían si se excluye a $exceptId. */
$adminsLeft = fn(int $exceptId) => (int) query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id <> ?", [$exceptId])->fetchColumn();

if (is_post()) {
    csrf_check();
    $action = input('accion');
    $id = (int) input('id');
    $user = $id ? query('SELECT * FROM users WHERE id = ?', [$id])->fetch() : null;
    $role = isset(ROLES[input('role')]) ? input('role') : 'asesor';
    $phone = mb_substr(input('phone'), 0, 40) ?: null;

    if ($action === 'crear') {
        $email = mb_strtolower(input('email'));
        $password = (string) ($_POST['password'] ?? '');
        if (input('name') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            flash('error', 'Completa nombre, un correo válido y una contraseña de al menos 10 caracteres.');
        } elseif (query('SELECT id FROM users WHERE email = ?', [$email])->fetch()) {
            flash('error', 'Ya existe un usuario con ese correo.');
        } else {
            query('INSERT INTO users (name, email, role, phone, password_hash) VALUES (?, ?, ?, ?, ?)',
                [input('name'), $email, $role, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Usuario creado. Entra en ' . url('admin/') . ' con su correo y la contraseña que escribiste.');
        }
    } elseif ($action === 'guardar' && $user) {
        $active = input('active') === '1' ? 1 : 0;
        if ($user['role'] === 'admin' && ($role !== 'admin' || !$active) && $adminsLeft($id) === 0) {
            flash('error', 'Debe quedar al menos un administrador activo.');
        } else {
            query('UPDATE users SET name = ?, role = ?, phone = ?, active = ? WHERE id = ?', [input('name') ?: $user['name'], $role, $phone, $active, $id]);
            if ((string) ($_POST['password'] ?? '') !== '') {
                if (strlen($_POST['password']) < 10) {
                    flash('error', 'La contraseña nueva debe tener al menos 10 caracteres; no se cambió.');
                } else {
                    query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $id]);
                }
            }
            flash('success', 'Usuario guardado.');
        }
    } elseif ($action === 'eliminar' && $user && $id !== (int) $me['id']) {
        if ($user['role'] === 'admin' && $adminsLeft($id) === 0) {
            flash('error', 'Debe quedar al menos un administrador activo.');
        } else {
            query('UPDATE properties SET agent_user_id = NULL WHERE agent_user_id = ?', [$id]);
            query('DELETE FROM users WHERE id = ?', [$id]);
            flash('success', 'Usuario eliminado. Sus inmuebles quedaron sin asesor.');
        }
    }
    redirect('admin/usuarios.php');
}

$users = query("SELECT u.*, (SELECT COUNT(*) FROM properties p WHERE p.agent_user_id = u.id) AS properties
    FROM users u ORDER BY u.active DESC, FIELD(u.role, 'admin', 'administrativo', 'contable', 'asesor'), u.name")->fetchAll();
$title = 'Usuarios';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Usuarios del panel</h1></div>

<section class="admin-card">
  <h2>Qué puede hacer cada rol</h2>
  <ul class="roles">
    <?php foreach (ROLES as $key => $label): ?><li><strong><?= e($label) ?>:</strong> <?= e(ROLE_DESCRIPTIONS[$key]) ?></li><?php endforeach; ?>
  </ul>
</section>

<?php foreach ($users as $u): $isMe = (int) $u['id'] === (int) $me['id']; ?>
  <form class="admin-card admin-zone user-row<?= $u['active'] ? '' : ' is-inactive' ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
    <div class="field"><label for="n<?= $u['id'] ?>">Nombre</label><input id="n<?= $u['id'] ?>" name="name" value="<?= e($u['name']) ?>"><span class="muted"><?= e($u['email']) ?><?= $isMe ? ' · tú' : '' ?> · <?= (int) $u['properties'] ?> inmueble(s)</span></div>
    <div class="field field--small"><label for="r<?= $u['id'] ?>">Rol</label>
      <select id="r<?= $u['id'] ?>" name="role"><?php foreach (ROLES as $k => $l): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field field--small"><label for="p<?= $u['id'] ?>">Celular</label><input id="p<?= $u['id'] ?>" name="phone" type="tel" value="<?= e($u['phone']) ?>"></div>
    <div class="field field--small"><label for="c<?= $u['id'] ?>">Nueva contraseña</label><input id="c<?= $u['id'] ?>" name="password" type="password" minlength="10" autocomplete="new-password" placeholder="Sin cambios"></div>
    <?php if ($isMe): ?><input type="hidden" name="active" value="1"><?php else: ?>
      <label class="check"><input type="checkbox" name="active" value="1" <?= $u['active'] ? 'checked' : '' ?>> <span>Activo</span></label>
    <?php endif; ?>
    <button class="btn btn--small" name="accion" value="guardar">Guardar</button>
    <?php if (!$isMe): ?><button class="btn btn--small btn--danger" name="accion" value="eliminar" data-confirm="¿Eliminar a <?= e($u['name']) ?>? Si solo quieres quitarle el acceso, desmarca Activo.">Eliminar</button><?php endif; ?>
  </form>
<?php endforeach; ?>

<form class="admin-card form" method="post">
  <?= csrf_field() ?>
  <h2>Agregar usuario</h2>
  <div class="form__row">
    <div class="field"><label for="u-name">Nombre</label><input id="u-name" name="name" required></div>
    <div class="field"><label for="u-email">Correo (para entrar)</label><input id="u-email" name="email" type="email" required autocomplete="off"></div>
    <div class="field"><label for="u-phone">Celular (se muestra en sus inmuebles)</label><input id="u-phone" name="phone" type="tel"></div>
  </div>
  <div class="form__row">
    <div class="field"><label for="u-role">Rol</label>
      <select id="u-role" name="role"><?php foreach (ROLES as $k => $l): ?><option value="<?= $k ?>" <?= $k === 'asesor' ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="u-pass">Contraseña (mínimo 10 caracteres)</label><input id="u-pass" name="password" type="password" minlength="10" required autocomplete="new-password"></div>
  </div>
  <div><button class="btn" name="accion" value="crear">Agregar usuario</button></div>
</form>
<?php view('admin_footer'); ?>
