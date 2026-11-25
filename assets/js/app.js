(function () {
    'use strict';

    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const POP_COLORS = ['#FF2A7A', '#1E40FF', '#FFC700', '#00B86B', '#FF5C1B', '#7C3AED', '#00C2FF'];

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

            const duration = 740;
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

    /* 2. Energetic 3D hover tilt on cards */
    function initTiltCards() {
        if (prefersReduced) return;
        const cards = document.querySelectorAll('.choice-card:not(:disabled), .level-card:not(.level-card-locked), .hero-doc-card');

        cards.forEach((card) => {
            card.addEventListener('pointermove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                const rotateY = (x - 0.5) * 8;
                const rotateX = (0.5 - y) * 8;

                card.style.transform = `perspective(700px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translate(-2px, -4px)`;
            });

            card.addEventListener('pointerleave', () => {
                card.style.transform = '';
            });
        });
    }

    /* 3. Maximalist click sparks + tactile ripple */
    function spawnSparks(clientX, clientY) {
        if (prefersReduced) return;
        const count = 8;
        for (let i = 0; i < count; i++) {
            const spark = document.createElement('span');
            spark.className = 'click-spark';
            const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.4;
            const dist = 26 + Math.random() * 32;
            spark.style.left = `${clientX - 5}px`;
            spark.style.top = `${clientY - 5}px`;
            spark.style.backgroundColor = POP_COLORS[Math.floor(Math.random() * POP_COLORS.length)];
            spark.style.setProperty('--dx', `${Math.cos(angle) * dist}px`);
            spark.style.setProperty('--dy', `${Math.sin(angle) * dist}px`);
            spark.style.setProperty('--rot', `${Math.round((Math.random() - 0.5) * 260)}deg`);
            document.body.appendChild(spark);
            window.setTimeout(() => spark.remove(), 540);
        }
    }

    function initRipplesAndSparks() {
        if (prefersReduced) return;
        const targets = document.querySelectorAll('.btn:not(:disabled), .choice-card:not(:disabled)');

        targets.forEach((el) => {
            el.addEventListener('pointerdown', (e) => {
                const rect = el.getBoundingClientRect();
                const ripple = document.createElement('span');
                ripple.className = 'ui-ripple';
                const size = Math.max(rect.width, rect.height) * 1.4;
                ripple.style.width = ripple.style.height = `${size}px`;
                ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
                ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
                el.appendChild(ripple);
                window.setTimeout(() => ripple.remove(), 550);

                spawnSparks(e.clientX, e.clientY);
            });
        });
    }

    /* 4. Auth tabs with bouncy sliding indicator */
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

    /* 5. Multi-shape maximalist confetti explosion */
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
        const count = trigger.getAttribute('data-confetti-intensity') === 'high' ? 120 : 72;

        for (let i = 0; i < count; i++) {
            const angle = -Math.PI / 2 + (Math.random() - 0.5) * 1.65;
            const speed = 6 + Math.random() * 10;
            pieces.push({
                x: window.innerWidth * (0.15 + Math.random() * 0.7),
                y: window.innerHeight * 0.22 + (Math.random() - 0.5) * 50,
                vx: Math.cos(angle) * speed + (Math.random() - 0.5) * 3.5,
                vy: Math.sin(angle) * speed - Math.random() * 4,
                size: 7 + Math.random() * 7,
                shape: i % 3, // 0: rect, 1: circle, 2: diamond
                rot: Math.random() * Math.PI * 2,
                vrot: (Math.random() - 0.5) * 0.26,
                color: POP_COLORS[i % POP_COLORS.length],
                alpha: 1,
            });
        }

        const start = performance.now();
        function frame(now) {
            const elapsed = now - start;
            ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);

            let alive = 0;
            pieces.forEach((p) => {
                p.vy += 0.24;
                p.vx *= 0.99;
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vrot;
                if (elapsed > 1700) {
                    p.alpha = Math.max(0, p.alpha - 0.018);
                }
                if (p.alpha > 0 && p.y < window.innerHeight + 50) {
                    alive++;
                    ctx.save();
                    ctx.globalAlpha = p.alpha;
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rot);
                    ctx.fillStyle = p.color;
                    ctx.strokeStyle = '#111111';
                    ctx.lineWidth = 1.5;

                    if (p.shape === 1) {
                        ctx.beginPath();
                        ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                        ctx.fill();
                        ctx.stroke();
                    } else if (p.shape === 2) {
                        ctx.beginPath();
                        ctx.moveTo(0, -p.size / 1.5);
                        ctx.lineTo(p.size / 1.5, 0);
                        ctx.lineTo(0, p.size / 1.5);
                        ctx.lineTo(-p.size / 1.5, 0);
                        ctx.closePath();
                        ctx.fill();
                        ctx.stroke();
                    } else {
                        ctx.fillRect(-p.size / 2, -p.size / 3, p.size, p.size * 0.66);
                        ctx.strokeRect(-p.size / 2, -p.size / 3, p.size, p.size * 0.66);
                    }
                    ctx.restore();
                }
            });

            if (alive > 0 && elapsed < 3600) {
                requestAnimationFrame(frame);
            } else {
                window.removeEventListener('resize', resize);
                canvas.remove();
            }
        }

        requestAnimationFrame(frame);
    }

    /* 6. Keyboard shortcuts in Ventanilla */
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

    /* 7. Staggered reveal index assignment */
    function initStagger() {
        const groups = document.querySelectorAll('.levels-grid, .guide-grid, .choice-grid, .inspector-fields');
        groups.forEach((group) => {
            Array.from(group.children).forEach((child, idx) => {
                child.style.setProperty('--stagger', `${idx * 55}ms`);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initStagger();
        initAuthTabs();
        initCounters();
        initTiltCards();
        initRipplesAndSparks();
        initConfetti();
        initVentanillaShortcuts();
    });
})();
