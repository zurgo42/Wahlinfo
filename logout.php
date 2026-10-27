<?php
/**
 * logout.php - Beendet die Session und leitet zur Welcome-Seite
 */

session_start();
session_destroy();
header('Location: welcome.php');
exit;
