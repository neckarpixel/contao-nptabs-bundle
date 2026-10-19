/*!
 * nptabs – barrierefreie Tabs für Contao
 * WAI-ARIA Authoring Practices "Tabs with Manual Activation":
 *   Pfeil links/rechts bewegt nur den Fokus, Enter/Leertaste aktiviert den Tab.
 *   Home/Ende springen zum ersten/letzten Tab. Tab-Taste führt in das Panel.
 *
 * Ohne JavaScript bleiben alle Inhalte sichtbar (Überschrift + Inhalt untereinander).
 * Öffentliche API: window.NpTabs.init(root?)  ·  Event "nptabs:change" (bubbles)
 */
(function () {
    'use strict';

    var READY = 'data-nptabs-ready';
    var reducedMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

    function NpTabs(root) {
        this.root = root;
        this.nav = child(root, '[data-nptabs-nav]');
        this.tablist = this.nav && this.nav.querySelector('[data-nptabs-tablist]');
        this.tabs = this.tablist ? Array.prototype.slice.call(this.tablist.querySelectorAll('[data-nptabs-tab]')) : [];
        this.panels = children(root, '[data-nptabs-panel]');
        this.current = -1;

        if (!this.tablist || this.tabs.length < 1 || this.tabs.length !== this.panels.length) {
            return; // Fallback: alles bleibt untereinander sichtbar
        }

        this.setup();
    }

    NpTabs.prototype.setup = function () {
        var self = this;

        this.panels.forEach(function (panel, i) {
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', self.tabs[i].id);
            // Panel fokussierbar, damit die Tab-Taste vom Tab direkt in den Inhalt führt
            panel.setAttribute('tabindex', '0');

            var heading = panel.querySelector(':scope > [data-nptabs-heading]');
            if (heading) {
                heading.hidden = true;
            }
        });

        this.nav.hidden = false;

        this.tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () {
                self.select(i);
            });
        });

        this.tablist.addEventListener('keydown', function (event) {
            self.onKeydown(event);
        });

        this.tablist.addEventListener('scroll', function () {
            self.updateOverflow();
        }, { passive: true });

        if (typeof ResizeObserver === 'function') {
            new ResizeObserver(function () {
                self.updateOverflow();
            }).observe(this.tablist);
        } else {
            window.addEventListener('resize', function () {
                self.updateOverflow();
            });
        }

        window.addEventListener('hashchange', function () {
            self.openFromHash(true);
        });

        // Startzustand: Hash vor Backend-Einstellung
        var start = parseInt(this.root.getAttribute('data-nptabs-active'), 10);
        start = isNaN(start) ? 0 : Math.min(Math.max(start, 0), this.tabs.length - 1);

        this.select(start, { silent: true, instant: true });
        this.root.setAttribute(READY, '');
        this.openFromHash(false);
        this.updateOverflow();
    };

    NpTabs.prototype.select = function (index, options) {
        options = options || {};
        index = Math.min(Math.max(index, 0), this.tabs.length - 1);

        var changed = index !== this.current;

        this.tabs.forEach(function (tab, i) {
            var active = i === index;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
            tab.classList.toggle('is-active', active);
        });

        this.panels.forEach(function (panel, i) {
            var active = i === index;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });

        this.current = index;
        this.revealTab(this.tabs[index], options.instant);

        if (changed && !options.silent) {
            this.root.dispatchEvent(new CustomEvent('nptabs:change', {
                bubbles: true,
                detail: { index: index, tab: this.tabs[index], panel: this.panels[index] }
            }));
        }
    };

    NpTabs.prototype.onKeydown = function (event) {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        var index = this.tabs.indexOf(document.activeElement);
        if (index === -1) {
            return;
        }

        var rtl = window.getComputedStyle(this.tablist).direction === 'rtl';
        var last = this.tabs.length - 1;
        var target = null;

        switch (event.key) {
            case 'ArrowRight':
                target = rtl ? (index > 0 ? index - 1 : last) : (index < last ? index + 1 : 0);
                break;
            case 'ArrowLeft':
                target = rtl ? (index < last ? index + 1 : 0) : (index > 0 ? index - 1 : last);
                break;
            case 'Home':
                target = 0;
                break;
            case 'End':
                target = last;
                break;
            default:
                return; // Enter/Leertaste lösen nativ einen Klick auf den Button aus
        }

        event.preventDefault();
        // Manuelle Aktivierung: nur Fokus bewegen, nicht aktivieren
        this.tabs[target].focus();
    };

    /**
     * Öffnet den Tab, der zum URL-Hash passt (Tab-ID, Panel-ID oder ein Element im Panel).
     */
    NpTabs.prototype.openFromHash = function (scroll) {
        var id = '';

        try {
            id = decodeURIComponent(window.location.hash.slice(1));
        } catch (e) {
            return;
        }

        if (!id) {
            return;
        }

        var target = document.getElementById(id);
        if (!target || !this.root.contains(target)) {
            return;
        }

        var index = this.tabs.indexOf(target);

        if (index === -1) {
            index = this.panels.findIndex(function (panel) {
                return panel === target || panel.contains(target);
            });
        }

        if (index === -1) {
            return;
        }

        this.select(index, { instant: !scroll });

        // Das Ziel war beim Sprung noch versteckt, daher jetzt erneut dorthin scrollen
        if (this.tabs.indexOf(target) === -1) {
            window.requestAnimationFrame(function () {
                target.scrollIntoView();
            });
        }
    };

    /**
     * Holt den aktiven Tab in den sichtbaren Bereich der Leiste (ohne die Seite zu scrollen).
     */
    NpTabs.prototype.revealTab = function (tab, instant) {
        var list = this.tablist;
        var listRect = list.getBoundingClientRect();
        var tabRect = tab.getBoundingClientRect();
        var padding = parseFloat(window.getComputedStyle(list).scrollPaddingInlineStart) || 0;
        var delta = 0;

        if (tabRect.left < listRect.left + padding) {
            delta = tabRect.left - listRect.left - padding;
        } else if (tabRect.right > listRect.right - padding) {
            delta = tabRect.right - listRect.right + padding;
        }

        if (delta !== 0) {
            list.scrollBy({
                left: delta,
                behavior: instant || (reducedMotion && reducedMotion.matches) ? 'auto' : 'smooth'
            });
        }
    };

    /**
     * Markiert, ob links/rechts noch Tabs außerhalb des sichtbaren Bereichs liegen (für den Ausblend-Effekt).
     */
    NpTabs.prototype.updateOverflow = function () {
        var list = this.tablist;
        var max = list.scrollWidth - list.clientWidth;
        var pos = Math.abs(list.scrollLeft);

        this.nav.toggleAttribute('data-overflow-start', max > 1 && pos > 1);
        this.nav.toggleAttribute('data-overflow-end', max > 1 && pos < max - 1);
    };

    function child(parent, selector) {
        return children(parent, selector)[0] || null;
    }

    function children(parent, selector) {
        return Array.prototype.filter.call(parent.children, function (el) {
            return el.matches(selector);
        });
    }

    var instances = new WeakMap();

    function init(scope) {
        var nodes = (scope || document).querySelectorAll('[data-nptabs]');

        Array.prototype.forEach.call(nodes, function (root) {
            if (!instances.has(root)) {
                instances.set(root, new NpTabs(root));
            }
        });
    }

    window.NpTabs = { init: init, instances: instances };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
        });
    } else {
        init();
    }
})();
