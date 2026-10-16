<?php
require __DIR__ . '/../../app/bootstrap.php';
$me = require_login();

if (is_post()) {
    csrf_check();
    $action = input('accion');
    if ($action === 'crear') {
        $email = input('email');
        $password = (string) ($_POST['password'] ?? '');
        if (input('name') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            flash('error', 'Completa nombre, un correo válido y una contraseña de al menos 10 caracteres.');
        } elseif (query('SELECT id FROM users WHERE email = ?', [$email])->fetch()) {
            flash('error', 'Ya existe un usuario con ese correo.');
        } else {
            query('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)', [input('name'), $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Usuario creado.');
        }
    } elseif ($action === 'clave') {
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 10) {
            flash('error', 'La contraseña debe tener al menos 10 caracteres.');
        } else {
            query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $me['id']]);
            flash('success', 'Contraseña actualizada.');
        }
    } elseif ($action === 'eliminar' && (int) input('id') !== (int) $me['id']) {
        query('DELETE FROM users WHERE id = ?', [(int) input('id')]);
        flash('success', 'Usuario eliminado.');
    }
    redirect('admin/usuarios.php');
}

$users = query('SELECT id, name, email, created_at FROM users ORDER BY name')->fetchAll();
$title = 'Usuarios';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Usuarios del panel</h1></div>
<div class="admin-columns">
  <section class="admin-card">
    <h2>Equipo</h2>
    <ul class="admin-list">
      <?php foreach ($users as $u): ?>
        <li class="admin-list__row">
          <span><strong><?= e($u['name']) ?></strong><br><span class="muted"><?= e($u['email']) ?></span></span>
          <?php if ((int) $u['id'] !== (int) $me['id']): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><button class="btn btn--small btn--danger" name="accion" value="eliminar" data-confirm="¿Quitar el acceso a <?= e($u['name']) ?>?">Quitar</button></form>
          <?php else: ?><span class="pill">Tú</span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <div>
    <form class="admin-card form" method="post">
      <?= csrf_field() ?>
      <h2>Agregar usuario</h2>
      <div class="field"><label for="u-name">Nombre</label><input id="u-name" name="name" required></div>
      <div class="field"><label for="u-email">Correo</label><input id="u-email" name="email" type="email" required autocomplete="off"></div>
      <div class="field"><label for="u-pass">Contraseña (mínimo 10 caracteres)</label><input id="u-pass" name="password" type="password" minlength="10" required autocomplete="new-password"></div>
      <button class="btn" name="accion" value="crear">Agregar</button>
    </form>
    <form class="admin-card form" method="post">
      <?= csrf_field() ?>
      <h2>Cambiar mi contraseña</h2>
      <div class="field"><label for="my-pass">Nueva contraseña</label><input id="my-pass" name="password" type="password" minlength="10" required autocomplete="new-password"></div>
      <button class="btn" name="accion" value="clave">Cambiar</button>
    </form>
  </div>
</div>
<?php view('admin_footer'); ?>
