<?php
session_start();
require_once dirname(__DIR__) . '/Database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Auth.php';

define('ADMIN_PATH', __DIR__);
