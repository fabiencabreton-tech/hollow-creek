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

function readStorage(key) {
    try {
        return JSON.parse(window.localStorage.getItem(key) || 'null');
    } catch {
        return null;
    }
}

function writeStorage(key, value) {
    try {
        if (value === null) {
            window.localStorage.removeItem(key);
        } else {
            window.localStorage.setItem(key, JSON.stringify(value));
        }
    } catch {
        // Stockage indisponible : le brouillon serveur reste la sauvegarde principale.
    }
}

function csrfToken(element) {
    const form = element.closest('form');
    const input = form ? form.querySelector('input[name="_token"]') : null;

    return input ? input.value : '';
}

function wrapSelection(textarea, before, after, placeholder) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.slice(start, end) || placeholder;
    const replacement = before + selected + after;

    textarea.setRangeText(replacement, start, end, 'end');
    textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function insertBlock(textarea, text) {
    const start = textarea.selectionStart;
    const prefix = start > 0 && textarea.value[start - 1] !== '\n' ? '\n\n' : '';

    textarea.setRangeText(prefix + text, start, textarea.selectionEnd, 'end');
    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function quoteSelection(textarea) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = textarea.value.slice(start, end) || 'Citation';
    const quoted = selected.split('\n').map((line) => '> ' + line).join('\n');

    textarea.setSelectionRange(start, end);
    insertBlock(textarea, quoted + '\n\n');
}

function setupEditor(editor) {
    const textarea = editor.querySelector('textarea');

    if (!textarea) {
        return;
    }

    const status = editor.querySelector('[data-editor-status]');
    const localKey = editor.getAttribute('data-local-key');
    const draftUrl = editor.getAttribute('data-draft-url');
    const serverSaved = Number(editor.getAttribute('data-server-saved') || 0);
    let timer = null;
    let submitting = false;
    let lastSent = textarea.value;

    const setStatus = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    editor.querySelectorAll('[data-editor-action]').forEach((button) => {
        button.addEventListener('click', () => {
            switch (button.getAttribute('data-editor-action')) {
                case 'bold':
                    wrapSelection(textarea, '**', '**', 'texte en gras');
                    break;
                case 'italic':
                    wrapSelection(textarea, '*', '*', 'texte en italique');
                    break;
                case 'dialogue': {
                    const previous = textarea.value[textarea.selectionStart - 1];
                    const space = previous && !/\s/.test(previous) ? ' ' : '';
                    wrapSelection(textarea, space + '« ', ' »', 'Réplique');
                    break;
                }
                case 'quote':
                    quoteSelection(textarea);
                    break;
                case 'separator':
                    insertBlock(textarea, '---\n\n');
                    break;
                default:
                    break;
            }
        });
    });

    // Copie de secours dans le navigateur, si la connexion tombe.
    if (localKey) {
        const stored = readStorage(localKey);
        const restore = editor.querySelector('[data-editor-restore]');

        if (stored && typeof stored.body === 'string' && stored.body.trim() !== ''
            && stored.body !== textarea.value && (stored.at || 0) > serverSaved && restore) {
            restore.hidden = false;
            restore.querySelector('[data-editor-restore-button]').addEventListener('click', () => {
                textarea.value = stored.body;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                restore.hidden = true;
            });
        }
    }

    const saveToServer = () => {
        if (!draftUrl || submitting || textarea.value === lastSent) {
            return;
        }

        const body = textarea.value;
        setStatus('Enregistrement…');

        fetch(draftUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(editor),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            keepalive: true,
            body: JSON.stringify({ body }),
        })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data) => {
                lastSent = body;
                setStatus('Brouillon enregistré à ' + data.saved_at + '.');
            })
            .catch(() => {
                setStatus('Hors ligne : le texte est gardé sur cet appareil.');
            });
    };

    textarea.addEventListener('input', () => {
        if (localKey) {
            writeStorage(localKey, { body: textarea.value, at: Date.now() });
        }

        if (draftUrl) {
            window.clearTimeout(timer);
            timer = window.setTimeout(saveToServer, 2500);
        }
    });

    // À l'envoi du formulaire, plus de sauvegarde : le brouillon serait recréé après publication.
    const form = editor.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            submitting = true;
            window.clearTimeout(timer);
        });
    }

    window.addEventListener('beforeunload', () => {
        if (!submitting && draftUrl && textarea.value !== lastSent) {
            saveToServer();
        }
    });
}

function setupEditorPreviews() {
    document.querySelectorAll('[data-editor-preview]').forEach((button) => {
        const form = button.closest('form');
        const editor = form ? form.querySelector('[data-editor]') : null;

        if (!editor) {
            return;
        }

        const textarea = editor.querySelector('textarea');
        const output = editor.querySelector('[data-editor-preview-output]');
        const target = editor.querySelector('[data-editor-preview-body]');

        button.addEventListener('click', (event) => {
            event.preventDefault();

            fetch(editor.getAttribute('data-preview-url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(editor),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ body: textarea.value }),
            })
                .then((response) => (response.ok ? response.json() : Promise.reject(response)))
                .then((data) => {
                    // Le HTML vient du serveur, qui neutralise tout balisage saisi.
                    target.innerHTML = data.html || '<p>Rien à afficher.</p>';
                    output.hidden = false;
                    output.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                })
                .catch(() => {
                    target.textContent = 'Aperçu indisponible pour le moment.';
                    output.hidden = false;
                });
        });
    });
}

function setupEditors() {
    document.querySelectorAll('[data-clear-local-key]').forEach((element) => {
        writeStorage(element.getAttribute('data-clear-local-key'), null);
    });

    document.querySelectorAll('[data-editor]').forEach(setupEditor);
    setupEditorPreviews();
}

const READING_KEY = 'hollow-creek-taille-lecture';

function setupReadingSize() {
    const reading = document.querySelector('[data-reading]');

    if (!reading) {
        return;
    }

    let size = Number(readStorage(READING_KEY)) || 1;

    const apply = () => {
        for (let i = 2; i <= 5; i += 1) {
            reading.classList.toggle('reading-size-' + i, size === i);
        }
    };

    apply();

    reading.querySelectorAll('[data-reading-size]').forEach((button) => {
        button.addEventListener('click', () => {
            size = Math.min(5, Math.max(1, size + Number(button.getAttribute('data-reading-size'))));
            writeStorage(READING_KEY, size);
            apply();
        });
    });
}

function setupCounters() {
    const source = document.querySelector('[data-counter-url]');

    if (!source) {
        return;
    }

    const url = source.getAttribute('data-counter-url');

    const refresh = () => {
        if (document.hidden) {
            return;
        }

        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data) => {
                ['notifications', 'messages'].forEach((key) => {
                    document.querySelectorAll('[data-counter="' + key + '"]').forEach((badge) => {
                        const count = Number(data[key] || 0);
                        badge.hidden = count === 0;
                        badge.firstChild.textContent = String(count);
                    });
                });
            })
            .catch(() => {});
    };

    window.setInterval(refresh, 60000);
}

function setupConversationPolling() {
    const list = document.querySelector('[data-poll-url]');

    if (!list) {
        return;
    }

    const url = list.getAttribute('data-poll-url');

    const poll = () => {
        if (document.hidden) {
            return;
        }

        const after = list.getAttribute('data-last-id') || '0';

        fetch(url + '?apres=' + encodeURIComponent(after), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data) => {
                if (data.html) {
                    // HTML produit par le serveur, texte des messages échappé.
                    list.insertAdjacentHTML('beforeend', data.html);
                    list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                list.setAttribute('data-last-id', String(data.last_id || after));
            })
            .catch(() => {});
    };

    window.setInterval(poll, 15000);
}

function setupTabs() {
    const nav = document.querySelector('[data-tabs]');

    if (!nav) {
        return;
    }

    const links = Array.from(nav.querySelectorAll('[data-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

    const show = (id) => {
        panels.forEach((panel) => {
            panel.hidden = panel.id !== id;
        });
        links.forEach((link) => {
            link.setAttribute('aria-current', link.getAttribute('href') === '#' + id ? 'true' : 'false');
        });
    };

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const id = link.getAttribute('href').slice(1);
            show(id);
            window.history.replaceState(null, '', '#' + id);
        });
    });

    const initial = window.location.hash.slice(1);
    show(panels.some((panel) => panel.id === initial) ? initial : panels[0]?.id);
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

function setupCityMap() {
    const root = document.querySelector('[data-city-map]');
    const source = root?.querySelector('[data-map-data]');

    if (!root || !source) {
        return;
    }

    let places;

    try {
        places = JSON.parse(source.textContent);
    } catch {
        return;
    }

    const panel = root.querySelector('[data-map-panel]');
    const empty = panel.querySelector('[data-map-empty]');
    const content = panel.querySelector('[data-map-content]');
    const media = panel.querySelector('[data-map-media]');
    const image = panel.querySelector('[data-map-image]');
    const credit = panel.querySelector('[data-map-credit]');
    const sceneList = panel.querySelector('[data-map-scenes]');
    const noScenes = panel.querySelector('[data-map-no-scenes]');
    const search = root.querySelector('[data-map-search]');
    const districtFilter = root.querySelector('[data-map-district]');
    const kindFilter = root.querySelector('[data-map-kind]');
    const count = root.querySelector('[data-map-count]');
    const links = Array.from(root.querySelectorAll('[data-place]'));
    const normalize = (value) => (value || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    const select = (slug) => {
        const place = places[slug];

        if (!place) {
            return;
        }

        links.forEach((link) => link.classList.toggle('is-selected', link.getAttribute('data-place') === slug));
        empty.hidden = true;
        content.hidden = false;

        media.hidden = !place.image;
        if (place.image) {
            image.src = place.image;
            image.alt = place.name;
            credit.textContent = place.imageCredit || '';
        }

        panel.querySelector('[data-map-meta]').textContent = [place.kind, place.district].filter(Boolean).join(' · ');
        panel.querySelector('[data-map-title]').textContent = place.name;
        panel.querySelector('[data-map-summary]').textContent = place.summary || 'La description de ce lieu sera bientôt complétée par l’équipe.';
        panel.querySelector('[data-map-start]').href = place.start;
        panel.querySelector('[data-map-link]').href = place.url;

        sceneList.replaceChildren(...place.scenes.map((scene) => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = scene.url;
            link.textContent = scene.title;
            item.append(link);
            return item;
        }));
        noScenes.hidden = place.scenes.length > 0;
    };

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey) {
                return;
            }
            event.preventDefault();
            select(link.getAttribute('data-place'));
            panel.querySelector('[data-map-title]').setAttribute('tabindex', '-1');
            if (link.closest('.map-list')) {
                panel.querySelector('[data-map-title]').focus();
            }
        });
    });

    const applyFilters = () => {
        const query = normalize(search.value.trim());
        const district = districtFilter.value;
        const kind = kindFilter.value;
        let visible = 0;

        Object.entries(places).forEach(([slug, place]) => {
            const matches = (!query || normalize(place.name).includes(query) || normalize(place.summary).includes(query))
                && (!district || place.district === district)
                && (!kind || place.kind === kind);

            if (matches) {
                visible++;
            }

            root.querySelectorAll(`[data-place="${CSS.escape(slug)}"]`).forEach((element) => {
                element.classList.toggle('is-filtered', !matches);
                if (element.closest('svg')) {
                    element.setAttribute('aria-hidden', matches ? 'false' : 'true');
                    element.setAttribute('tabindex', matches ? '0' : '-1');
                }
            });
            const item = root.querySelector(`[data-map-item="${CSS.escape(slug)}"]`);
            if (item) {
                item.hidden = !matches;
            }
        });

        count.textContent = visible === 1 ? '1 lieu' : `${visible} lieux`;
    };

    [search, districtFilter, kindFilter].forEach((control) => control.addEventListener('input', applyFilters));
    root.querySelector('[data-map-filters]').addEventListener('submit', (event) => event.preventDefault());
    applyFilters();
}

function setupNatureForm() {
    const form = document.querySelector('[data-nature-form]');

    if (!form) {
        return;
    }

    const natureInputs = Array.from(form.querySelectorAll('[data-nature]'));
    const traitInputs = Array.from(form.querySelectorAll('[data-trait]'));
    const supernaturalNatures = (form.getAttribute('data-supernatural-natures') || '').split(',').filter(Boolean);
    const list = (element, attribute) => (element.getAttribute(attribute) || '').split(',').filter(Boolean);

    const update = () => {
        const nature = natureInputs.find((input) => input.checked)?.getAttribute('data-nature') || '';

        traitInputs.forEach((input) => {
            const allowed = list(input.closest('[data-allowed-natures]'), 'data-allowed-natures');
            const permitted = allowed.length === 0 || allowed.includes(nature);
            input.disabled = !permitted;
            if (!permitted) {
                input.checked = false;
            }
        });

        const traits = traitInputs.filter((input) => input.checked).map((input) => input.getAttribute('data-trait'));
        const supernatural = supernaturalNatures.includes(nature) || traits.length > 0;

        form.querySelectorAll('[data-show-for-natures], [data-show-for-traits], [data-supernatural]').forEach((group) => {
            let visible = true;

            if (group.hasAttribute('data-show-for-natures')) {
                visible = visible && list(group, 'data-show-for-natures').includes(nature);
            }
            if (group.hasAttribute('data-show-for-traits')) {
                visible = visible && list(group, 'data-show-for-traits').some((trait) => traits.includes(trait));
            }
            if (group.hasAttribute('data-supernatural')) {
                visible = visible && supernatural;
            }

            group.hidden = !visible;
        });
    };

    form.addEventListener('change', update);
    update();
}

function setupFamilyTree() {
    document.querySelectorAll('[data-family-tree]').forEach((tree) => {
        const nodes = new Map();
        tree.querySelectorAll('[data-node]').forEach((element) => {
            nodes.set(element.getAttribute('data-node'), {
                element,
                parents: (element.getAttribute('data-parents') || '').split(',').filter(Boolean),
                spouse: element.getAttribute('data-spouse') || '',
            });
        });
        const edges = Array.from(tree.querySelectorAll('.family-tree__edge'));
        const children = new Map();
        nodes.forEach((node, id) => node.parents.forEach((parent) => {
            if (!children.has(parent)) children.set(parent, []);
            children.get(parent).push(id);
        }));

        const collect = (start, next) => {
            const seen = new Set();
            const stack = [...next(start)];
            while (stack.length) {
                const id = stack.pop();
                if (seen.has(id) || !nodes.has(id)) continue;
                seen.add(id);
                stack.push(...next(id));
            }
            return seen;
        };

        const light = (id) => {
            const lit = new Set([id]);
            collect(id, (current) => nodes.get(current)?.parents || []).forEach((other) => lit.add(other));
            collect(id, (current) => children.get(current) || []).forEach((other) => lit.add(other));
            const spouse = nodes.get(id)?.spouse;
            if (spouse) lit.add(spouse);

            tree.classList.add('is-tracing');
            nodes.forEach((node, key) => node.element.classList.toggle('is-lit', lit.has(key)));
            edges.forEach((edge) => edge.classList.toggle('is-lit', lit.has(edge.getAttribute('data-from')) && lit.has(edge.getAttribute('data-to'))));
        };

        const clear = () => {
            tree.classList.remove('is-tracing');
            tree.querySelectorAll('.is-lit').forEach((element) => element.classList.remove('is-lit'));
        };

        nodes.forEach((node, id) => {
            node.element.addEventListener('mouseenter', () => light(id));
            node.element.addEventListener('focus', () => light(id));
            node.element.addEventListener('mouseleave', clear);
            node.element.addEventListener('blur', clear);
        });
    });
}

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    setupThemeToggle();
    setupNavigation();
    setupPasswordToggles();
    setupWordCounters();
    setupEditors();
    setupReadingSize();
    setupCounters();
    setupConversationPolling();
    setupTabs();
    setupConfirmations();
    setupCopyButtons();
    setupCityMap();
    setupNatureForm();
    setupFamilyTree();
});
