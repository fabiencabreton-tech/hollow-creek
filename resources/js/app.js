/*
 * Hollow Creek — petits comportements de l’interface publique.
 * Aucun script tiers, aucun traceur. Le site reste utilisable sans JavaScript.
 */

const THEME_KEY = 'hollow-creek-theme';

function readTheme() {
    try {
        return window.localStorage.getItem(THEME_KEY);
    } catch {
        return null;
    }
}

function writeTheme(value) {
    try {
        if (value) {
            window.localStorage.setItem(THEME_KEY, value);
        } else {
            window.localStorage.removeItem(THEME_KEY);
        }
    } catch {
        // Stockage indisponible (navigation privée) : le choix vaut pour la page en cours.
    }
}

function applyTheme(value) {
    if (value === 'parchemin') {
        document.documentElement.setAttribute('data-theme', 'parchemin');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const isParchment = value === 'parchemin';
        button.setAttribute('aria-pressed', isParchment ? 'true' : 'false');
        button.textContent = isParchment ? 'Mode nuit' : 'Mode parchemin';
    });
}

function setupThemeToggle() {
    applyTheme(readTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => {
            const next = readTheme() === 'parchemin' ? null : 'parchemin';
            writeTheme(next);
            applyTheme(next);
        });
    });
}

function setupNavigation() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.getElementById('site-nav');

    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });
}

function countWords(text) {
    const trimmed = text.trim();

    return trimmed === '' ? 0 : trimmed.split(/\s+/u).length;
}

function setupWordCounters() {
    document.querySelectorAll('textarea[data-word-count]').forEach((textarea) => {
        const output = document.getElementById(textarea.getAttribute('data-word-count'));

        if (!output) {
            return;
        }

        const update = () => {
            const words = countWords(textarea.value);
            output.textContent = words + (words > 1 ? ' mots' : ' mot');
        };

        textarea.addEventListener('input', update);
        update();
    });
}

function setupPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));

        if (!input) {
            return;
        }

        button.hidden = false;
        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
            button.textContent = isVisible ? 'Afficher' : 'Masquer';
        });
    });
}

function setupConfirmations() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });
}

function setupCopyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        const target = document.getElementById(button.getAttribute('data-copy-target'));

        if (!target || !navigator.clipboard) {
            return;
        }

        button.hidden = false;
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(target.innerText.trim());
                button.textContent = 'Copié';
            } catch {
                button.textContent = 'Copie impossible';
            }
        });
    });
}

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupNavigation();
    setupPasswordToggles();
    setupWordCounters();
    setupConfirmations();
    setupCopyButtons();
});
