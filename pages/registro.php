<?php
require_once __DIR__ . '/../includes/config.php';
// El registro manual ya no existe — todo pasa por Google/Firebase
// Si alguien entra a esta URL directamente, lo mandamos al login
redirect(APP_URL . '/pages/login.php');
