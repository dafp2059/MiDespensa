<?php
require __DIR__ . '/../../app/bootstrap.php';

if (is_post()) {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect('admin/login.php');
