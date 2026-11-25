(function () {
    'use strict';

    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const PORTAL_COLORS = ['#15284B', '#2F5D80', '#1E7A4F', '#D9A441', '#5B8FB9'];

    /* 1. Animated count-up for numeric metrics */
    function initCounters() {
        const els = document.querySelectorAll('[data-count-to]');
        if (!els.length) return;

        els.forEach((el) => {
            const target = parseInt(el.getAttribute('data-count-to') || '0', 10);
            const prefix = el.getAttribute('data-prefix') || '';
            const suffix = el.getAttribute('data-suffix') || '';

            if (prefersReduced || isNaN(target) || target === 0) {
                el.textContent = prefix + target + suffix;
                return;
            }

            const duration = 620;
            const startTime = performance.now();

            function tick(now) {
                const progress = Math.min(1, (now - startTime) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(eased * target);
                el.textContent = prefix + current + suffix;
                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            }

            el.textContent = prefix + '0' + suffix;
            requestAnimationFrame(tick);
        });
    }

    /* 2. Auth tabs with smooth sliding indicator on index.php */
    function initAuthTabs() {
        const switcher = document.querySelector('[data-auth-tabs]');
        if (!switcher) return;

        const buttons = switcher.querySelectorAll('[data-tab-target]');
        const panels = document.querySelectorAll('[data-tab-panel]');
        const indicator = switcher.querySelector('.auth-tab-indicator');

        function activateTab(tabName) {
            buttons.forEach((btn, idx) => {
                const active = btn.getAttribute('data-tab-target') === tabName;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
                if (active && indicator) {
                    indicator.style.transform = `translateX(${idx * 100}%)`;
                }
            });

            panels.forEach((panel) => {
                const match = panel.getAttribute('data-tab-panel') === tabName;
                panel.classList.toggle('is-active', match);
                panel.hidden = !match;
            });
        }

        const initial = switcher.getAttribute('data-initial-tab') || 'login';
        activateTab(initial);

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-tab-target');
                if (target) activateTab(target);
            });
        });
    }

    /* 3. Tasteful confetti burst on approved summary */
    function initConfetti() {
        if (prefersReduced) return;
        const trigger = document.querySelector('[data-confetti="true"]');
        if (!trigger) return;

        const canvas = document.createElement('canvas');
        canvas.className = 'confetti-canvas';
        canvas.setAttribute('aria-hidden', 'true');
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            canvas.remove();
            return;
        }

        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        function resize() {
            canvas.width = window.innerWidth * dpr;
            canvas.height = window.innerHeight * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }
        resize();
        window.addEventListener('resize', resize);

        const pieces = [];
        const count = trigger.getAttribute('data-confetti-intensity') === 'high' ? 75 : 44;

        for (let i = 0; i < count; i++) {
            const angle = -Math.PI / 2 + (Math.random() - 0.5) * 1.4;
            const speed = 5 + Math.random() * 7;
            pieces.push({
                x: window.innerWidth * (0.2 + Math.random() * 0.6),
                y: window.innerHeight * 0.2 + (Math.random() - 0.5) * 30,
                vx: Math.cos(angle) * speed + (Math.random() - 0.5) * 2.5,
                vy: Math.sin(angle) * speed - Math.random() * 3,
                w: 7 + Math.random() * 5,
                h: 4 + Math.random() * 4,
                rot: Math.random() * Math.PI * 2,
                vrot: (Math.random() - 0.5) * 0.2,
                color: PORTAL_COLORS[i % PORTAL_COLORS.length],
                alpha: 1,
            });
        }

        const start = performance.now();
        function frame(now) {
            const elapsed = now - start;
            ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);

            let alive = 0;
            pieces.forEach((p) => {
                p.vy += 0.22;
                p.vx *= 0.99;
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vrot;
                if (elapsed > 1400) {
                    p.alpha = Math.max(0, p.alpha - 0.02);
                }
                if (p.alpha > 0 && p.y < window.innerHeight + 40) {
                    alive++;
                    ctx.save();
                    ctx.globalAlpha = p.alpha;
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rot);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                    ctx.restore();
                }
            });

            if (alive > 0 && elapsed < 3000) {
                requestAnimationFrame(frame);
            } else {
                window.removeEventListener('resize', resize);
                canvas.remove();
            }
        }

        requestAnimationFrame(frame);
    }

    /* 4. Keyboard shortcuts in Ventanilla */
    function initVentanillaShortcuts() {
        const shell = document.querySelector('.ventanilla-shell');
        if (!shell) return;

        document.addEventListener('keydown', (e) => {
            if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;
            if (e.metaKey || e.ctrlKey || e.altKey) return;

            const key = e.key.toLowerCase();

            if (['1', '2', '3', '4', '5'].includes(key)) {
                const btn = shell.querySelector(`.choice-pos-${key}:not(:disabled)`);
                if (btn) {
                    e.preventDefault();
                    btn.classList.add('is-pressing');
                    btn.click();
                }
                return;
            }

            if (key === 'r') {
                const rejectBtn = shell.querySelector('.btn-reject:not(:disabled)');
                if (rejectBtn) {
                    e.preventDefault();
                    rejectBtn.click();
                }
                return;
            }
            if (key === 'a') {
                const approveBtn = shell.querySelector('.btn-approve:not(:disabled)');
                if (approveBtn) {
                    e.preventDefault();
                    approveBtn.click();
                }
                return;
            }

            if (key === 'enter') {
                const nextBtn = shell.querySelector('.feedback-next-form button[type="submit"]');
                if (nextBtn) {
                    e.preventDefault();
                    nextBtn.click();
                }
            }
        });
    }

    /* 5. Staggered reveal index assignment */
    function initStagger() {
        const groups = document.querySelectorAll('.levels-portal-grid, .guide-grid, .choice-steel-grid');
        groups.forEach((group) => {
            Array.from(group.children).forEach((child, idx) => {
                child.style.setProperty('--stagger', `${idx * 45}ms`);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initStagger();
        initAuthTabs();
        initCounters();
        initConfetti();
        initVentanillaShortcuts();
    });
})();
