const LUCIDE_RETRY_LIMIT = 40;
const LUCIDE_RETRY_DELAY = 75;

let lucideScriptRequested = false;

function pageHasLucideIcons() {
    return Boolean(document.querySelector('[data-lucide]'));
}

function ensureLucideScript() {
    if (window.lucide?.createIcons || lucideScriptRequested) {
        return;
    }

    if (! pageHasLucideIcons()) {
        return;
    }

    if (document.querySelector('script[src*="lucide"]')) {
        lucideScriptRequested = true;
        return;
    }

    lucideScriptRequested = true;

    const script = document.createElement('script');
    script.src = 'https://unpkg.com/lucide@latest';
    script.defer = true;
    script.addEventListener('load', () => refreshLucideIcons());

    document.head.appendChild(script);
}

function refreshLucideIcons(attempt = 0) {
    if (! pageHasLucideIcons()) {
        return;
    }

    if (window.lucide?.createIcons) {
        window.lucide.createIcons();
        return;
    }

    ensureLucideScript();

    if (attempt < LUCIDE_RETRY_LIMIT) {
        window.setTimeout(() => refreshLucideIcons(attempt + 1), LUCIDE_RETRY_DELAY);
    }
}

window.refreshLucideIcons = refreshLucideIcons;

document.addEventListener('DOMContentLoaded', () => refreshLucideIcons());
document.addEventListener('livewire:navigated', () => refreshLucideIcons());
window.addEventListener('load', () => refreshLucideIcons());

function formatCountUpValue(value, format) {
    if (format === 'currency') {
        return `Rp ${new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
        }).format(value)}`;
    }

    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(value);
}

function animateCountUp(root) {
    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    root.querySelectorAll('[data-count-up]').forEach((element) => {
        if (element.dataset.countAnimated === '1') {
            return;
        }

        const target = Number(element.dataset.countValue ?? element.textContent.replace(/[^\d.-]/g, ''));
        const format = element.dataset.countFormat || 'number';

        if (! Number.isFinite(target)) {
            return;
        }

        element.dataset.countAnimated = '1';

        if (prefersReducedMotion) {
            element.textContent = formatCountUpValue(target, format);
            return;
        }

        const duration = Number(element.dataset.countDuration || 900);
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = formatCountUpValue(Math.round(target * eased), format);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        element.textContent = formatCountUpValue(0, format);
        requestAnimationFrame(tick);
    });
}

function initUserDashboard() {
    const dashboard = document.querySelector('[data-user-dashboard]');

    if (! dashboard) {
        return;
    }

    dashboard.classList.remove('is-loading');
    animateCountUp(dashboard);
}

document.addEventListener('DOMContentLoaded', initUserDashboard);
document.addEventListener('livewire:navigated', initUserDashboard);
window.addEventListener('pageshow', initUserDashboard);
