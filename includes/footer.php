
<!-- FOOTER -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <a href="<?= APP_URL ?>/index.php" class="footer-logo">
                <img src="<?= APP_URL ?>/images/logo.png" alt="<?= APP_NAME ?>" class="footer-logo-img">
            </a>
            <p>La plataforma de lectura digital más completa en español. Accedé a miles de ebooks con una sola suscripción.</p>
            <div class="footer-social">
<a href="https://www.facebook.com/bookvon.ok/" aria-label="Facebook">
    <svg viewBox="0 0 24 24" fill="currentColor" width="18">
        <path d="M13 22v-8h3l1-4h-4V7.5c0-1.1.3-1.8 1.9-1.8H17V2.1C16.6 2 15.4 2 14 2c-2.9 0-5 1.8-5 5.2V10H6v4h3v8h4z"/>
    </svg>
</a>
            
            </div>
        </div>

        <div class="footer-links">
            <div class="footer-col">
                <h4>Catálogo</h4>
                <a href="<?= APP_URL ?>/pages/catalogo.php">Todos los libros</a>
                <a href="<?= APP_URL ?>/pages/catalogo.php?filter=destacados">Destacados</a>
                <a href="<?= APP_URL ?>/pages/catalogo.php?filter=nuevos">Novedades</a>
                <a href="<?= APP_URL ?>/pages/categorias.php">Categorías</a>
            </div>
            <div class="footer-col">
                <h4>Suscripción</h4>
                <a href="<?= APP_URL ?>/pages/suscripcion.php">Planes y precios</a>
                <a href="<?= APP_URL ?>/pages/suscripcion.php#faq">Preguntas frecuentes</a>
                <a href="<?= APP_URL ?>/pages/registro.php">Crear cuenta</a>
            </div>
            <div class="footer-col">
                <h4>Empresa</h4>
                <a href="#">Sobre nosotros</a>
                <a href="#">Contacto</a>
                <a href="#">Términos de uso</a>
                <a href="#">Privacidad</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos los derechos reservados.</p>
        <p>Hecho con ❤️ para lectores digitales</p>
    </div>
</footer>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
