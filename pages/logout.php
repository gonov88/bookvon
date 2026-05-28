<?php // logout.php
require_once __DIR__ . '/../includes/config.php';
session_destroy();
redirect(APP_URL . '/index.php');
