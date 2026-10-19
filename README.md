# contao-nptabs-bundle

Barrierefreie, responsive Tabs für Contao 5.7 (PHP 8.4), WCAG 2.2 AA.

- Verschachteltes Inhaltselement – genau wie das Core-Accordion: ein Element „Tabs“, darunter beliebig viele Kind-Elemente. Jedes Kind ist ein Tab, die **Abschnittsüberschrift** des Kindes ist die Tab-Beschriftung
- WAI-ARIA Authoring Practices „Tabs with Manual Activation“ (`role=tablist/tab/tabpanel`)
- Mobil: scrollbare Tab-Leiste mit Ausblend-Effekt, aktiver Tab wird in den Blick gescrollt
- Progressive Enhancement: ohne JavaScript stehen alle Inhalte mit Überschrift untereinander
- Vanilla JS (ca. 8 KB, keine Abhängigkeiten), CSS ohne Layer (niedrige Spezifität, optional als Cascade Layer ausgebbar), SCSS zum Anpassen

## Installation (lokales Paket)

```json
// composer.json der Installation
"repositories": [{ "type": "path", "url": "bundles/contao-nptabs-bundle" }]
```

```bash
php84 composer.phar require neckarpixel/contao-nptabs-bundle:@dev
php84 vendor/bin/contao-console contao:migrate
php84 vendor/bin/contao-console cache:clear
```

`contao:migrate` legt die Felder `nptabs_label` und `nptabs_activeTab` in `tl_content` an. `public/` wird als `bundles/nptabs/` veröffentlicht (CSS + JS).

## Einrichtung im Backend

1. Inhaltselement **Tabs** (Kategorie „Verschiedenes“, neben dem Accordion) anlegen
   - Überschrift (optional), Bezeichnung der Tab-Liste (`aria-label`, leer = Überschrift), aktiver Tab beim Laden
2. Im Tabs-Element auf „Unterelemente bearbeiten“ klicken und die Inhalte als Kind-Elemente anlegen (Text, Bild, Formular, …)
3. Bei jedem Kind-Element die **Abschnittsüberschrift** ausfüllen – das ist die Tab-Beschriftung. Die Überschriften-Ebene (h1–h6) wird nur im Fallback ohne JavaScript und beim Drucken angezeigt. Ohne Eintrag heißt der Tab „Tab 1“, „Tab 2“, …

Das Feld `sectionHeadline` ergänzt das Bundle bei Kindern eines Tabs-Elements selbst in der Palette, falls der Core es nicht schon tut.

## Konfiguration

```yaml
# config/config.yaml
np_tabs:
    load_default_css: false   # Standard-CSS nicht laden, wenn das Theme alles selbst stylt
```

Alternativ im Template nur die Datei tauschen: `{% set tabs_css_file = asset('css/meine-tabs.css') %}`.

## Tastatur & Screenreader

| Taste | Wirkung |
|---|---|
| ← / → | Fokus auf vorherigen/nächsten Tab (springt am Rand um, bei RTL gespiegelt) |
| Home / Ende | Fokus auf ersten/letzten Tab |
| Enter / Leertaste | Tab aktivieren (manuelle Aktivierung) |
| Tab | vom aktiven Tab ins Panel (Panel hat `tabindex=0`) |

- Roving `tabindex`: nur der aktive Tab ist per Tab-Taste erreichbar
- Fokusring innen (`outline-offset: -3px`), weil die scrollbare Leiste einen äußeren Ring abschneiden würde
- Tabs mindestens 44 px hoch, aktiver Tab zusätzlich über Rahmen statt nur Farbe markiert (Windows-Kontrastmodus: `Highlight`)
- `prefers-reduced-motion`: keine Übergänge, kein sanftes Scrollen
- Druck: Leiste aus, alle Panels mit Überschrift

**Deep Links:** `#tabs-tab-<id>`, `#tabs-panel-<id>` oder die ID eines beliebigen Elements innerhalb eines Panels öffnen den passenden Tab (beim Laden und bei `hashchange`). `<id>` ist die ID des Kind-Elements.

**Event:** `tabs:change` (bubbles) mit `detail: { index, tab, panel }`. Nachträglich eingefügtes Markup: `window.ContaoTabs.init(container)`.

## Markup

```
div.tabs[data-tabs]
├─ div.tabs__nav                     (data-overflow-start/-end bei überstehenden Tabs)
│  └─ div.tabs__tablist[role=tablist]
│     └─ button.tabs__tab[role=tab][aria-selected][aria-controls]
└─ div.tabs__panel[role=tabpanel][aria-labelledby]
   ├─ h2.tabs__heading               (nur ohne JS sichtbar)
   └─ div.tabs__content
```

Template überschreibbar über `templates/content_element/tabs.html.twig`, Blöcke: `tabs_tablist`, `tabs_tab`, `tabs_panel`. Zusätzliche Attribute über `tabs_attributes`, `tabs_nav_attributes`, `tabs_tablist_attributes`, `tabs_tab_attributes`, `tabs_panel_attributes`.

## Eigenes Styling

Nach der Installation ist das Standard-CSS sofort aktiv (`bundles/nptabs/tabs.css`, wird nur geladen, wenn ein Tabs-Element auf der Seite liegt). Je nach Bedarf gibt es drei Wege.

### Weg 1 – Werte per Custom Properties (reicht meistens)

Im Theme-CSS die `--tabs-*`-Properties setzen, ohne Layer:

```css
.tabs {
    --tabs-color-accent: #e30613;
    --tabs-tab-padding-inline: 1.5rem;
    --tabs-radius: 99px;
}
```

Kein Build, kein `!important`. Das Standard-CSS bleibt aktiv.

### Weg 2 – Einzelne Regeln überschreiben

Wenn Properties nicht reichen, einfache Selektoren im Theme-CSS, ebenfalls ohne Layer:

```css
.tabs__tab[aria-selected='true'] { text-decoration: underline; }
```

Das gewinnt immer gegen das Bundle-CSS. Globale Theme-Regeln für `button` schlagen ebenfalls durch und müssen ggf. für `.tabs__tab` zurückgesetzt werden.

### Weg 3 – Komplett per SCSS

1. Standard-CSS abschalten:

   ```yaml
   # config/config.yaml
   np_tabs:
       load_default_css: false
   ```

2. Bundle-SCSS im eigenen SCSS einbinden und Variablen setzen (`assets/scss/`: `_variables.scss` mit allen Werten als `!default`, `_tabs.scss` mit dem Mixin `styles`):

   ```scss
   @use 'pfad/zu/tabs/assets/scss/variables' with (
       $color-accent: #e30613,
       $tab-padding-inline: 1.5rem,
       $layer: tabs             // optional: als Cascade Layer ausgeben (Standard: false)
   );
   @use 'pfad/zu/tabs/assets/scss/tabs';
   @include tabs.styles;
   ```

   `@use … with` muss vor jedem anderen Laden der Variablen stehen.

3. Das kompilierte CSS im Theme laden.

Das mitgelieferte CSS neu bauen: `npm install && npm run build` (Dart Sass → `public/tabs.css`).

Wer die HTML-Struktur ändern muss, kopiert `tabs.html.twig` nach `templates/content_element/` und passt es dort an.

### Welche Property steuert was

![Schema der Tab-Leiste mit nummerierten Markierungen für jede Custom Property](docs/tabs-properties.svg)

| Nr. | Property | Standard | Wirkung |
|---|---|---|---|
| 1 | `--tabs-color-border` | `#c4c7c5` | Trennlinie unter der Leiste |
| 2 | `--tabs-color-accent` | `#0b57d0` | Linie unter dem aktiven Tab |
| 2 | `--tabs-indicator-width` | `3px` | Dicke dieser Linie |
| 3 | `--tabs-color-text` | `#1c1c1c` | Text des aktiven und des gehoverten Tabs |
| 4 | `--tabs-bg-hover` | `rgba(11, 87, 208, 0.08)` | Hintergrund bei Hover |
| 4 | `--tabs-bg-active` | `transparent` | Hintergrund des aktiven Tabs |
| 5 | `--tabs-color-text-muted` | `#4a4a4a` | Text inaktiver Tabs |
| 6 | `--tabs-color-focus` | `#0b57d0` | Fokusring von Tab und Panel |
| 7 | `--tabs-tab-padding-block` / `-inline` | `0.75rem` / `1.25rem` | Innenabstand der Tabs |
| 8 | `--tabs-tab-min-height` | `2.75rem` | Mindesthöhe der Tabs (44 px, Zielgröße) |
| 9 | `--tabs-tab-gap` | `0` | Abstand zwischen den Tabs |
| 10 | `--tabs-radius` | `0.25rem` | Rundung der oberen Tab-Ecken (`99px` = Pille) |
| 11 | `--tabs-fade-size` | `2.5rem` | Breite des Ausblendens bei überstehenden Tabs |
| 12 | `--tabs-tab-font-size` / `-font-weight` | `1rem` / `600` | Schrift der Tabs |
| 13 | `--tabs-panel-padding` | `1.5rem 0` | Innenabstand des Panels (Kurzschreibweise wie `padding`, 1–4 Werte) |
| 14 | `--tabs-duration` | `0.15s` | Dauer der Übergänge |

Die SCSS-Variablen in `_variables.scss` heißen genauso, nur ohne Präfix (z. B. `$color-accent`, `$panel-padding`).

### Cascade Layer und Rangfolge

Das Standard-CSS wird **ohne Layer** ausgeliefert. Alle Selektoren sind einfache Klassen (Spezifität 0-1-0), eigene Regeln mit gleicher oder höherer Spezifität gewinnen über die Ladereihenfolge. Außerdem schlagen sie typische Reset-Regeln wie `button { … }` oder `div { padding: 0 }`.

Der Grund: Ein Layer-CSS verliert immer gegen ungelayertes CSS. Enthält die Seite einen Reset ohne Layer (z. B. Contaos `reset.css`), würden dessen Regeln `font-size`, `font-weight` und `padding` der Tabs überschreiben.

Wer das Layer-Verhalten trotzdem will (Seite komplett in Layern organisiert), gibt das CSS per SCSS mit `$layer: tabs` aus (siehe Weg 3). Dann gilt:

| Eigene Regeln … | Ergebnis |
|---|---|
| ohne Layer | gewinnen immer, egal bei welcher Spezifität oder Ladereihenfolge |
| in einem Layer, der nach `tabs` kommt | gewinnen |
| im selben Layer `tabs` | Spezifität, dann Ladereihenfolge |

Reihenfolge festlegen (ganz oben im CSS): `@layer vendor, tabs, theme;`

## Hinweise

- Verschachtelte Tabs (Tabs in Tabs) funktionieren; Hash-Links öffnen beide Ebenen
- Panel-Inhalt ist per `hidden` ausgeblendet (nicht per `display:none` im CSS), das schützt auch bei Theme-Resets
- Im Tab-Text keine Block-Elemente verwenden; die Abschnittsüberschrift ist ein einzeiliges Textfeld

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).
