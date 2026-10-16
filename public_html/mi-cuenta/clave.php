<?php
// Crear o cambiar la contraseña: con el enlace que envía la inmobiliaria, o estando dentro de Mi cuenta.
require __DIR__ . '/../../app/bootstrap.php';

$token = input('t');
$client = $token !== '' ? client_by_token($token) : current_client();
$error = '';

if ($client && is_post()) {
    csrf_check();
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== (string) ($_POST['password2'] ?? '')) {
        $error = 'Las dos contraseñas no coinciden.';
    } else {
        query('UPDATE clients SET password_hash = ?, last_login_at = NOW() WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $client['id']]);
        query('DELETE FROM client_tokens WHERE client_id = ?', [$client['id']]);
        session_regenerate_id(true);
        $_SESSION['client_id'] = $client['id'];
        flash('success', 'Listo, tu contraseña quedó guardada.');
        redirect('mi-cuenta/');
    }
}

$page = ['title' => 'Crear contraseña', 'noindex' => true];
view('header', compact('page'));
?>
<section class="section">
  <div class="container narrow">
    <?php if (!$client): ?>
      <div class="panel account-login">
        <h1 class="page-head__title">El enlace ya no es válido</h1>
        <p>Puede que haya vencido o que ya lo hayas usado. Pide uno nuevo a tu asesor o usa "Olvidé mi contraseña".</p>
        <a class="btn" href="<?= e(url('mi-cuenta/olvide.php')) ?>">Olvidé mi contraseña</a>
      </div>
    <?php else: ?>
      <form class="form panel account-login" method="post">
        <?= csrf_field() ?>
        <h1 class="page-head__title">Hola, <?= e(explode(' ', $client['name'])[0]) ?></h1>
        <p class="muted">Crea tu contraseña para entrar a Mi cuenta con el correo <strong><?= e($client['email']) ?></strong>.</p>
        <?php if ($error): ?><p class="alert alert--error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="username" value="<?= e($client['email']) ?>" autocomplete="username">
        <div class="field"><label for="password">Nueva contraseña (mínimo 8 caracteres)</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="field"><label for="password2">Repite la contraseña</label><input id="password2" name="password2" type="password" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn--block" type="submit">Guardar contraseña</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php view('footer'); ?>
