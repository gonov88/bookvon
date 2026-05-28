// =====================================================
// LEXIREAD — MAIN JS
// =====================================================

document.addEventListener('DOMContentLoaded', () => {

    // ─── HAMBURGER ───
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            hamburger.classList.toggle('open');
        });
    }

    // ─── FAVORITE TOGGLE (AJAX) ───
    const appUrl = document.body.dataset.appUrl || '';
    document.querySelectorAll('.fav-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const libroId = this.dataset.id;
            const svgPath = this.querySelector('path');
            try {
                const res = await fetch(`${appUrl}/pages/ajax/toggle_fav.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `libro_id=${libroId}`
                });
                const data = await res.json();
                if (data.success) {
                    this.classList.toggle('active', data.isFav);
                    this.title = data.isFav ? 'Quitar de favoritos' : 'Añadir a favoritos';
                    // Actualizar ícono relleno/vacío
                    if (svgPath) svgPath.setAttribute('fill', data.isFav ? 'currentColor' : 'none');
                    // Actualizar texto del botón
                    const textNodes = [...this.childNodes].filter(n => n.nodeType === 3);
                    if (textNodes.length) textNodes[textNodes.length - 1].textContent = data.isFav ? ' Guardado' : ' Guardar';
                } else if (data.msg) {
                    alert(data.msg);
                }
            } catch (e) { console.error('Fav error', e); }
        });
    });

    // ─── BUSCADOR CON SUGERENCIAS EN TIEMPO REAL ───
    const searchInput = document.getElementById('nav-search-input');
    const sugBox      = document.getElementById('search-suggestions');

    if (searchInput && sugBox) {
        let debounceTimer = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const q = searchInput.value.trim();
            if (q.length < 2) { sugBox.classList.remove('open'); sugBox.innerHTML = ''; return; }

            debounceTimer = setTimeout(async () => {
                try {
                    const res  = await fetch(`${appUrl}/pages/ajax/search_suggest.php?q=` + encodeURIComponent(q));
                    const data = await res.json();

                    if (!data.length) { sugBox.classList.remove('open'); sugBox.innerHTML = ''; return; }

                    sugBox.innerHTML = data.map(item => `
                        <a href="${item.url}" class="sug-item">
                            ${item.portada
                                ? `<img src="${item.portada}" class="sug-thumb" alt="" onerror="this.style.display='none'">`
                                : `<div class="sug-thumb-placeholder">📚</div>`}
                            <div>
                                <div class="sug-title">${item.titulo}</div>
                                <div class="sug-meta">${item.autor ? item.autor + ' · ' : ''}${item.categoria}</div>
                            </div>
                        </a>
                    `).join('') + `<a href="${appUrl}/pages/buscar.php?q=${encodeURIComponent(q)}" class="sug-footer">Ver todos los resultados para "${q}" →</a>`;

                    sugBox.classList.add('open');
                } catch(e) { /* silencioso */ }
            }, 280);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', e => {
            if (!searchInput.closest('.search-form').contains(e.target)) {
                sugBox.classList.remove('open');
            }
        });

        // Navegar con teclado
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Escape') { sugBox.classList.remove('open'); searchInput.blur(); }
        });
    }


    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // ─── SUBCATEGORY FILTER (catalog sidebar) ───
    document.querySelectorAll('.filter-item[data-cat]').forEach(item => {
        item.addEventListener('click', function () {
            const cat = this.dataset.cat;
            const url = new URL(window.location);
            if (cat) url.searchParams.set('categoria', cat);
            else url.searchParams.delete('categoria');
            url.searchParams.delete('page');
            window.location = url.toString();
        });
    });

    // ─── SORT SELECT ───
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const url = new URL(window.location);
            url.searchParams.set('orden', this.value);
            url.searchParams.delete('page');
            window.location = url.toString();
        });
    }

    // ─── SMOOTH SCROLL for anchor links ───
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // ─── HERO CAROUSEL ───
    (function () {
        const track    = document.getElementById('heroCarouselTrack');
        const dotsWrap = document.getElementById('heroDots');
        const btnPrev  = document.getElementById('hcPrev');
        const btnNext  = document.getElementById('hcNext');
        if (!track) return;

        const cards     = track.querySelectorAll('.hero-carousel-card');
        const total     = cards.length;
        if (total === 0) return;

        // How many cards visible at once (based on width)
        function visibleCount() {
            const w = track.parentElement.offsetWidth;
            if (w < 400) return 2;
            if (w < 600) return 3;
            return 4;
        }

        let current   = 0;
        let autoTimer = null;
        const CARD_W  = 150 + 14; // width + gap
        const pages   = () => Math.max(1, total - visibleCount() + 1);

        // Build dots
        function buildDots() {
            dotsWrap.innerHTML = '';
            const p = pages();
            for (let i = 0; i < p; i++) {
                const d = document.createElement('button');
                d.className = 'hc-dot' + (i === current ? ' active' : '');
                d.addEventListener('click', () => goTo(i));
                dotsWrap.appendChild(d);
            }
        }

        function goTo(idx) {
            const p = pages();
            current = Math.max(0, Math.min(idx, p - 1));
            track.style.transform = `translateX(-${current * CARD_W}px)`;
            dotsWrap.querySelectorAll('.hc-dot').forEach((d, i) => d.classList.toggle('active', i === current));
        }

        function next() { goTo(current + 1 < pages() ? current + 1 : 0); }
        function prev() { goTo(current - 1 >= 0 ? current - 1 : pages() - 1); }

        btnNext.addEventListener('click', () => { next(); resetAuto(); });
        btnPrev.addEventListener('click', () => { prev(); resetAuto(); });

        function startAuto() { autoTimer = setInterval(next, 3200); }
        function resetAuto() { clearInterval(autoTimer); startAuto(); }

        // Pause on hover
        track.parentElement.addEventListener('mouseenter', () => clearInterval(autoTimer));
        track.parentElement.addEventListener('mouseleave', startAuto);

        // Touch swipe
        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) diff > 0 ? next() : prev();
            resetAuto();
        });

        buildDots();
        startAuto();
        window.addEventListener('resize', () => { buildDots(); goTo(current); });
    })();

});
