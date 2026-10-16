<?php
require __DIR__ . '/../../app/bootstrap.php';

if (is_post()) {
    csrf_check();
    unset($_SESSION['client_id']);
    session_regenerate_id(true);
}
redirect('mi-cuenta/ingresar.php');
