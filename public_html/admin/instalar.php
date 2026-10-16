<?php
// Crea el primer usuario administrador. Solo funciona mientras no exista ningún usuario.
require __DIR__ . '/../../app/bootstrap.php';

if (query('SELECT id FROM users LIMIT 1')->fetch()) {
    redirect('admin/login.php');
}

$errors = [];
if (is_post()) {
    csrf_check();
    $name = input('name');
    $email = input('email');
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '') $errors[] = 'Escribe tu nombre.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Revisa el correo.';
    if (strlen($password) < 10) $errors[] = 'La contraseña debe tener al menos 10 caracteres.';
    if (!$errors) {
        query('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)', [$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();
        flash('success', 'Usuario creado. ¡Bienvenido al panel!');
        redirect('admin/');
    }
}

$title = 'Crear administrador';
view('admin_header', compact('title'));
?>
<form class="form panel admin-login" method="post">
  <?= csrf_field() ?>
  <h1 class="page-head__title">Crear el primer administrador</h1>
  <p class="muted">Este paso solo aparece una vez.</p>
  <?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>
  <div class="field"><label for="name">Nombre</label><input id="name" name="name" required value="<?= e(input('name')) ?>"></div>
  <div class="field"><label for="email">Correo</label><input id="email" name="email" type="email" required autocomplete="username" value="<?= e(input('email')) ?>"></div>
  <div class="field"><label for="password">Contraseña (mínimo 10 caracteres)</label><input id="password" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
  <button class="btn btn--block" type="submit">Crear y entrar</button>
</form>
<?php view('admin_footer'); ?>
