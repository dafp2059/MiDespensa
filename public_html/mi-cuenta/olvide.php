<?php
require __DIR__ . '/../../app/bootstrap.php';

$sent = false;
if (is_post()) {
    csrf_check();
    $client = query('SELECT * FROM clients WHERE email = ? AND active = 1', [mb_strtolower(input('email'))])->fetch();
    // Máximo un correo cada 5 minutos por cliente. La respuesta es igual exista o no el correo.
    if ($client && time() - ($_SESSION['reset_' . $client['id']] ?? 0) > 300) {
        $_SESSION['reset_' . $client['id']] = time();
        send_mail($client['email'], 'Crea una nueva contraseña – ' . site('name'),
            "Hola {$client['name']},\n\nPara crear una nueva contraseña de Mi cuenta abre este enlace (vence en 7 días):\n\n"
            . client_access_link((int) $client['id']) . "\n\nSi no lo pediste, ignora este correo.\n\n" . site('name'));
    }
    $sent = true;
}
$page = ['title' => 'Olvidé mi contraseña', 'noindex' => true];
view('header', compact('page'));
?>
<section class="section">
  <div class="container narrow">
    <form class="form panel account-login" method="post">
      <?= csrf_field() ?>
      <h1 class="page-head__title">Olvidé mi contraseña</h1>
      <?php if ($sent): ?>
        <p class="alert alert--success">Si el correo está registrado, te enviamos un enlace para crear una nueva contraseña. Revisa también la carpeta de spam.</p>
      <?php endif; ?>
      <div class="field"><label for="email">Tu correo</label><input id="email" name="email" type="email" required autocomplete="username"></div>
      <button class="btn btn--block" type="submit">Enviarme el enlace</button>
      <p class="muted">¿No te llega? Pídele el enlace a tu asesor por <a href="<?= e(whatsapp_link('Hola, necesito un enlace para crear mi contraseña de Mi cuenta.')) ?>" target="_blank" rel="noopener">WhatsApp</a>. <a href="<?= e(url('mi-cuenta/ingresar.php')) ?>">Volver</a></p>
    </form>
  </div>
</section>
<?php view('footer'); ?>
