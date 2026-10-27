<?php
require_once __DIR__ . '/src/bootstrap.php';
ka_logout();
header('Location: ' . ka_url('login.php'));
exit;
