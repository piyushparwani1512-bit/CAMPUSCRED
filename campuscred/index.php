<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_by_role();
}

header('Location: ' . base_url('login.php'));
exit;
