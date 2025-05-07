<?php
// auth/logout.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;