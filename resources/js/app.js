const LUCIDE_RETRY_LIMIT = 40;
const LUCIDE_RETRY_DELAY = 75;

let lucideScriptRequested = false;

function ensureLucideScript() {
    if (window.lucide?.createIcons || lucideScriptRequested) {
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
