<?php
require_once __DIR__ . '/config.php';
$currentUser = getCurrentUser();
$hasSubscription = hasActiveSubscription();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? sanitize($pageDesc) : 'Plataforma de ebooks por suscripción' ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
</head>
<body data-app-url="<?= APP_URL ?>">

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= APP_URL ?>/index.php" class="nav-logo">
            <img src="<?= APP_URL ?>/images/logo.png" alt="<?= APP_NAME ?>" class="nav-logo-img">
        </a>

        <div class="nav-search">
            <form action="<?= APP_URL ?>/pages/buscar.php" method="GET" class="search-form" autocomplete="off">
                <div class="search-box" id="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" name="q" id="nav-search-input"
                           placeholder="Buscar títulos, autores, categorías..."
                           value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                           autocomplete="off">
                    <button type="submit" class="search-submit-btn" aria-label="Buscar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
                <div class="search-suggestions" id="search-suggestions"></div>
            </form>
        </div>

        <div class="nav-links">
            <a href="<?= APP_URL ?>/pages/catalogo.php" class="nav-link <?= $currentPage === 'catalogo.php' ? 'active' : '' ?>">Catálogo</a>
            <a href="<?= APP_URL ?>/pages/categorias.php" class="nav-link <?= $currentPage === 'categorias.php' ? 'active' : '' ?>">Categorías</a>
            <?php if ($currentUser): ?>
                <?php if (!$hasSubscription): ?>
                    <a href="<?= APP_URL ?>/pages/suscripcion.php" class="btn-primary-sm">Suscribirse</a>
                <?php endif; ?>
                <div class="nav-user-menu">
                    <button class="user-trigger">
                        <div class="user-avatar"><?= strtoupper(substr($currentUser['nombre'], 0, 1)) ?></div>
                        <span><?= explode(' ', $currentUser['nombre'])[0] ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="user-dropdown">
                        <a href="<?= APP_URL ?>/pages/mi-biblioteca.php">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            Mi Biblioteca
                        </a>
                        <a href="<?= APP_URL ?>/pages/favoritos.php">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                            Favoritos
                        </a>
                        <a href="<?= APP_URL ?>/pages/perfil.php">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Mi Perfil
                        </a>
                        <?php if ($currentUser['rol'] === 'admin'): ?>
                        <a href="<?= APP_URL ?>/admin/index.php">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Panel Admin
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= APP_URL ?>/pages/logout.php" class="logout-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Cerrar sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= APP_URL ?>/pages/login.php" class="nav-link">Ingresar</a>
                <a href="<?= APP_URL ?>/pages/registro.php" class="btn-primary-sm">Registrarse</a>
            <?php endif; ?>
        </div>

        <button class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <form action="<?= APP_URL ?>/pages/buscar.php" method="GET" class="mobile-search">
            <input type="text" name="q" placeholder="Buscar libros...">
            <button type="submit">Buscar</button>
        </form>
        <a href="<?= APP_URL ?>/pages/catalogo.php">Catálogo</a>
        <a href="<?= APP_URL ?>/pages/categorias.php">Categorías</a>
        <?php if ($currentUser): ?>
            <a href="<?= APP_URL ?>/pages/mi-biblioteca.php">Mi Biblioteca</a>
            <a href="<?= APP_URL ?>/pages/perfil.php">Mi Perfil</a>
            <a href="<?= APP_URL ?>/pages/logout.php">Cerrar sesión</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/pages/login.php">Ingresar</a>
            <a href="<?= APP_URL ?>/pages/registro.php">Registrarse</a>
        <?php endif; ?>
    </div>
</nav>
