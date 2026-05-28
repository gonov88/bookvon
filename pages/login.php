<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Iniciar sesión';

if (isLoggedIn()) { redirect(APP_URL . '/index.php'); }

include __DIR__ . '/../includes/header.php';
?>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <a href="<?= APP_URL ?>/index.php">
                <img src="<?= APP_URL ?>/images/logo.png" alt="<?= APP_NAME ?>" class="auth-logo-img">
            </a>
        </div>
        <h1>Bienvenido de vuelta</h1>
        <p class="auth-subtitle">Ingresá con tu cuenta de Google para continuar leyendo</p>

        <div id="auth-error" class="alert alert-error" style="display:none;"></div>

        <button id="btn-google-login" class="btn-google-auth" type="button">
            <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Continuar con Google
        </button>

        <div class="auth-footer">
            ¿Primera vez? Al ingresar con Google tu cuenta se crea automáticamente.
        </div>
    </div>
</div>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
<script>
(function(){
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyBgdiJOK7yFUZldCW6M1rLZShflx8T5dfA",
  authDomain: "ebook-bba6c.firebaseapp.com",
  projectId: "ebook-bba6c",
  storageBucket: "ebook-bba6c.firebasestorage.app",
  messagingSenderId: "597313416820",
  appId: "1:597313416820:web:c410b2663ca2277a89ab21",
  measurementId: "G-6ZDTL7JDML"
};

    firebase.initializeApp(firebaseConfig);
    const auth     = firebase.auth();
    const provider = new firebase.auth.GoogleAuthProvider();
    const btn      = document.getElementById('btn-google-login');
    const errBox   = document.getElementById('auth-error');

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.textContent = 'Conectando…';
        errBox.style.display = 'none';
        try {
            const result  = await auth.signInWithPopup(provider);
            const idToken = await result.user.getIdToken();
            const redirect = new URLSearchParams(location.search).get('redirect') || '';
            const url = '<?= APP_URL ?>/pages/ajax/firebase_auth.php' + (redirect ? '?redirect=' + encodeURIComponent(redirect) : '');
            const res  = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idToken })
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errBox.textContent = data.error || 'Error al iniciar sesión.';
                errBox.style.display = 'flex';
                btn.disabled = false;
                btn.textContent = 'Continuar con Google';
            }
        } catch (err) {
            errBox.textContent = 'No se pudo conectar con Google. Revisá que las popups no estén bloqueadas.';
            errBox.style.display = 'flex';
            btn.disabled = false;
            btn.textContent = 'Continuar con Google';
        }
    });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>