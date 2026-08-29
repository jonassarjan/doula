<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Auth.php';

Auth::logout();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
