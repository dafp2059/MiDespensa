<?php
require __DIR__ . '/../../app/bootstrap.php';

if (current_client()) {
    redirect('mi-cuenta/');
}
$error = '';
if (is_post()) {
    csrf_check();
    $attempts = $_SESSION['client_attempts'] ?? 0;
    if ($attempts >= 5 && time() - ($_SESSION['client_last'] ?? 0) < 600) {
        $error = 'Demasiados intentos. Espera 10 minutos o usa "Olvidé mi contraseña".';
    } else {
        $client = query('SELECT * FROM clients WHERE email = ? AND active = 1', [mb_strtolower(input('email'))])->fetch();
        if ($client && $client['password_hash'] && password_verify((string) ($_POST['password'] ?? ''), $client['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['client_id'] = $client['id'];
            unset($_SESSION['client_attempts'], $_SESSION['client_last']);
            query('UPDATE clients SET last_login_at = NOW() WHERE id = ?', [$client['id']]);
            redirect('mi-cuenta/');
        }
        $_SESSION['client_attempts'] = $attempts + 1;
        $_SESSION['client_last'] = time();
        usleep(400000);
        $error = 'Correo o contraseña incorrectos.';
    }
}

$page = ['title' => 'Mi cuenta', 'noindex' => true];
view('header', compact('page'));
?>
<section class="section">
  <div class="container narrow">
    <form class="form panel account-login" method="post">
      <?= csrf_field() ?>
      <h1 class="page-head__title">Mi cuenta</h1>
      <p class="muted">Consulta tu contrato, paga tus cuotas en línea y descarga tus recibos.</p>
      <?php if ($error): ?><p class="alert alert--error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <div class="field"><label for="email">Correo</label><input id="email" name="email" type="email" required autocomplete="username" value="<?= e(input('email')) ?>"></div>
      <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
      <button class="btn btn--block" type="submit">Entrar</button>
      <p class="muted"><a href="<?= e(url('mi-cuenta/olvide.php')) ?>">Olvidé mi contraseña</a> · ¿Aún no tienes acceso? Pídelo a tu asesor por <a href="<?= e(whatsapp_link('Hola, quiero el acceso a Mi cuenta.')) ?>" target="_blank" rel="noopener">WhatsApp</a>.</p>
    </form>
  </div>
</section>
<?php view('footer'); ?>
