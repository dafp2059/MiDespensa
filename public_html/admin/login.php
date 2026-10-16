<?php
require __DIR__ . '/../../app/bootstrap.php';

if (!query('SELECT id FROM users LIMIT 1')->fetch()) {
    redirect('admin/instalar.php');
}
if (current_user()) {
    redirect('admin/');
}

$error = '';
if (is_post()) {
    csrf_check();
    $attempts = $_SESSION['login_attempts'] ?? 0;
    if ($attempts >= 5 && time() - ($_SESSION['login_last'] ?? 0) < 600) {
        $error = 'Demasiados intentos. Espera 10 minutos e inténtalo de nuevo.';
    } else {
        $user = query('SELECT * FROM users WHERE email = ?', [input('email')])->fetch();
        if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['login_attempts'], $_SESSION['login_last']);
            redirect('admin/');
        }
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['login_last'] = time();
        usleep(500000);
        $error = 'Correo o contraseña incorrectos.';
    }
}

$title = 'Ingresar';
view('admin_header', compact('title'));
?>
<form class="form panel admin-login" method="post">
  <?= csrf_field() ?>
  <h1 class="page-head__title">Panel de administración</h1>
  <?php if ($error): ?><p class="alert alert--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <div class="field">
    <label for="email">Correo</label>
    <input id="email" name="email" type="email" required autocomplete="username" value="<?= e(input('email')) ?>">
  </div>
  <div class="field">
    <label for="password">Contraseña</label>
    <input id="password" name="password" type="password" required autocomplete="current-password">
  </div>
  <button class="btn btn--block" type="submit">Ingresar</button>
</form>
<?php view('admin_footer'); ?>
