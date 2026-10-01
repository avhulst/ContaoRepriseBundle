# Contao Reprise Bundle – Anleitung

Diese Anleitung beschreibt, wie Sie Vite-Assets (CSS, JavaScript) über das Bundle
`trassd/contao-reprise-bundle` in Contao einbinden – vom ersten Build bis zum Deployment.
Alle Beispieldateien liegen kopierfertig im Ordner [`examples/`](../examples).

## Inhalt

1. [Funktionsweise](#1-funktionsweise)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Reprise konfigurieren](#4-reprise-konfigurieren)
5. [Vite einrichten](#5-vite-einrichten)
6. [Dev-Server unter DDEV](#6-dev-server-unter-ddev)
7. [Entrypoints im Seitenlayout wählen](#7-entrypoints-im-seitenlayout-wählen)
8. [Mehrere Entries und Builds](#8-mehrere-entries-und-builds)
9. [Entwicklung, Produktion und Deployment](#9-entwicklung-produktion-und-deployment)
10. [Eigene Templates](#10-eigene-templates)
11. [Fehlerverhalten und Troubleshooting](#11-fehlerverhalten-und-troubleshooting)
12. [Rsbuild statt Vite](#12-rsbuild-statt-vite)

## 1. Funktionsweise

```
layout/app.js ──vite build──▶ public/layout/entrypoints.json + app-<hash>.js/.css
                                          │
Seitenlayout (Backend): Vite-Entrypoints = [app]
                                          │
Seitenaufruf ─▶ EntrypointTagInjector ─▶ <link>/<script> im <head>
```

- Das [Vite-Plugin von Reprise](https://github.com/symfony/reprise) schreibt bei jedem Build
  (und beim Start des Dev-Servers) eine `entrypoints.json`. Sie enthält pro Entry die
  zugehörigen JS- und CSS-Dateien.
- Das Bundle registriert `symfony/reprise` in Contao und ergänzt die Seitenlayouts um das
  Feld **Vite-Entrypoints**.
- Beim Seitenaufruf werden für die gewählten Entries die `<link>`- und `<script>`-Tags in den
  `<head>` geschrieben – erst alle Stylesheets, dann alle Skripte, in der gewählten
  Reihenfolge. Dateien, die sich mehrere Entries teilen, werden nur einmal eingebunden.
- Das funktioniert für **klassische** Layouts (`fe_page`) und **moderne** Layouts
  (`page/layout`). Ein Template-Override ist nicht nötig.

## 2. Voraussetzungen

| Komponente | Version |
|---|---|
| PHP | 8.4 (Voraussetzung von `symfony/reprise`) |
| Contao | 5.7 |
| Node.js | eine von Vite 8 unterstützte Version |
| Vite | ≥ 8.2 (für ältere Versionen siehe [Abschnitt 5](#5-vite-einrichten)) |
| npm-Paket | `@symfony/reprise` ^1.3 |

## 3. Installation

```bash
composer require trassd/contao-reprise-bundle
vendor/bin/contao-console contao:migrate
```

Die Migration legt die Spalte `tl_layout.reprise_entrypoints` an. `symfony/reprise` wird
automatisch mitinstalliert und über das Manager-Plugin registriert – ein eigener
`App\ContaoManager\Plugin` für `RepriseBundle` ist nicht nötig (bestehenden entfernen).

Unter DDEV entsprechend mit `ddev composer …` und `ddev exec vendor/bin/contao-console …`.

## 4. Reprise konfigurieren

Beispiel: [`examples/config/config.yaml`](../examples/config/config.yaml)

```yaml
# config/config.yaml
reprise:
  output_path: "%kernel.project_dir%/public/layout"
```

`output_path` muss auf dasselbe Verzeichnis zeigen wie `outputPath` in der Vite-Konfiguration.
Ohne Angabe verwendet Reprise `public/build`.

| Option | Standard | Bedeutung |
|---|---|---|
| `output_path` | `%kernel.project_dir%/public/build` | Verzeichnis der `entrypoints.json` des Standard-Builds |
| `builds` | `{}` | Weitere Builds mit eigener `entrypoints.json` ([Abschnitt 8](#8-mehrere-entries-und-builds)) |
| `strict_mode` | `true` | Fehlt die Datei oder ein Entry, wird eine Exception geworfen. Mit `false` wird nichts ausgegeben. |
| `cache` | `false` | Geparste `entrypoints.json` cachen (beim `cache:warmup`). Für Produktion empfohlen, benötigt `symfony/cache`. |
| `crossorigin` | `false` | `crossorigin`-Attribut neben SRI-`integrity` (`anonymous`, `use-credentials`) |
| `script_attributes` / `link_attributes` | `{}` | Zusätzliche Attribute für jedes `<script>` bzw. `<link>` |

`framework.assets.json_manifest_path` ist **nur** nötig, wenn Sie in Templates `{{ asset('layout/…') }}`
verwenden, etwa für Bilder, die mit der Vite-Option `copy` ins Build kopiert werden.

## 5. Vite einrichten

### Pakete

```bash
npm install --save-dev vite @symfony/reprise
# oder: yarn add --dev vite @symfony/reprise
```

Skripte für die `package.json` (Beispiel: [`examples/package.json`](../examples/package.json)):

```json
{
    "scripts": {
        "serve": "vite",
        "watch": "vite build --watch --mode development",
        "build": "vite build"
    }
}
```

### Entry anlegen

Ein Entry ist eine JavaScript-Datei. CSS/SCSS, das Sie dort importieren, landet als eigene
CSS-Datei im Build und wird als `<link>` eingebunden
(Beispiel: [`examples/layout/app.js`](../examples/layout/app.js)):

```js
// layout/app.js
import "./app.css";
```

### `vite.config.mjs`

Minimal, ohne Dev-Server-Proxy ([`examples/vite.config.minimal.mjs`](../examples/vite.config.minimal.mjs)):

```js
import { defineConfig } from "vite";
import Symfony from "@symfony/reprise/vite";

export default defineConfig({
	input: {
		app: "./layout/app.js",
	},
	plugins: [
		Symfony({
			outputPath: "public/layout",
			publicPath: "/layout/",
		}),
	],
});
```

- Die Schlüssel unter `input` (hier `app`) sind die **Entry-Namen**, die Sie später im
  Seitenlayout auswählen.
- `outputPath` ist relativ zur `package.json`, `publicPath` die zugehörige URL.
- Vite ≤ 8.1 kennt `input` auf oberster Ebene nicht; dort gehören die Entries nach
  `build.rollupOptions.input`.

Die vollständige DDEV-Variante mit Dev-Server, HMR, Full-Reload und SRI steht in
[`examples/vite.config.mjs`](../examples/vite.config.mjs) und wird im nächsten Abschnitt erklärt.

Weitere Plugin-Optionen von `@symfony/reprise/vite`:

| Option | Bedeutung |
|---|---|
| `outputPath` | Zielverzeichnis des Builds |
| `publicPath` | Öffentliche URL von `outputPath` (auch absolute CDN-URL möglich) |
| `metadataPath` | Eigenes Verzeichnis für `entrypoints.json`/`manifest.json`, z. B. bei Auslieferung über ein CDN. Dann muss `reprise.output_path` auf dieses Verzeichnis zeigen. |
| `manifestKeyPrefix` | Präfix der Manifest-Schlüssel; nur bei absolutem `publicPath` nötig |
| `devServerOrigin` | Öffentliche Adresse des Dev-Servers (hinter Proxy/Docker nötig) |
| `integrity` | Subresource-Integrity-Hashes, z. B. `{ enabled: command === "build", algorithms: ["sha384"] }` |
| `copy` | Statische Dateien kopieren und im Manifest registrieren |
| `stimulus` | Symfony-UX-/Stimulus-Controller registrieren |

### Bauen

```bash
npm run build
```

Danach liegen in `public/layout/` die gehashten Dateien sowie `entrypoints.json` und
`manifest.json`.

## 6. Dev-Server unter DDEV

Der Vite-Dev-Server läuft im Web-Container auf Port 8080. Damit der Browser ihn erreicht,
muss der Port über den DDEV-Router freigegeben werden
([`examples/.ddev/config.watcher.yaml`](../examples/.ddev/config.watcher.yaml)):

```yaml
# .ddev/config.watcher.yaml
web_extra_exposed_ports:
  - name: vite
    container_port: 8080
    http_port: 8081
    https_port: 8080
```

Anschließend `ddev restart`. Die relevanten Teile der
[`examples/vite.config.mjs`](../examples/vite.config.mjs):

```js
const primaryUrl = process.env.DDEV_PRIMARY_URL || "https://example.ddev.site";
const devServerOrigin = `${primaryUrl}:8080`;

Symfony({ outputPath, publicPath, devServerOrigin });

server: {
	host: "0.0.0.0",          // im Container von außen erreichbar
	port: 8080,
	strictPort: true,
	allowedHosts: true,
	cors: { origin: primaryUrl },
	hmr: { protocol: "wss", host: new URL(primaryUrl).hostname, clientPort: 8080 },
},
```

- `devServerOrigin` sorgt dafür, dass `entrypoints.json` im Dev-Modus auf
  `https://<projekt>.ddev.site:8080/…` zeigt statt auf `0.0.0.0`.
- `hmr` leitet den Hot-Module-Replacement-Websocket über HTTPS (`wss`) durch den DDEV-Router.
- Das kleine Plugin `fullReload` lädt die Seite neu, sobald sich Dateien in `templates/`,
  `contao/`, `config/`, `src/` oder `translations/` ändern – also alles, was Contao serverseitig
  rendert. CSS/JS-Änderungen werden ohne Reload per HMR übernommen.

Starten:

```bash
ddev exec npm run serve
```

Das Bundle bindet im Dev-Modus automatisch den Vite-Client (`@vite/client`) ein – genau einmal,
auch wenn mehrere Entries gewählt sind.

## 7. Entrypoints im Seitenlayout wählen

**Layout → Themes → Seitenlayouts → Layout bearbeiten**

- Klassische Layouts: Feld **Vite-Entrypoints** im Abschnitt „Stylesheets“.
- Moderne Layouts: eigener Abschnitt **Vite-Assets**.

Die Auswahl listet alle Entries aus den `entrypoints.json`-Dateien aller konfigurierten Builds.
Die Einträge lassen sich sortieren; die Reihenfolge bestimmt die Reihenfolge im `<head>`.

Gespeichert wird:

| Build | Gespeicherter Wert | Anzeige im Backend |
|---|---|---|
| Standard (`reprise.output_path`) | `app` | `app` |
| benannt (`reprise.builds.landing`) | `landing:main` | `main (landing)` |

Fehlt die `entrypoints.json` noch (vor dem ersten Build), ist die Liste leer – bauen Sie zuerst.
Werte, die es nach einem Umbau nicht mehr gibt, zeigt Contao als „unbekannte Option“ an, damit
beim Speichern nichts verloren geht.

## 8. Mehrere Entries und Builds

### Mehrere Entries in einem Build

```js
input: {
	app: "./layout/app.js",
	landing: "./layout/landing.js",
},
```

Im Layout „Standard“ wählen Sie `app`, im Layout „Landingpage“ `app` und `landing`. Gemeinsam
genutzte Chunks (z. B. Bootstrap) werden dabei nur einmal eingebunden.

### Mehrere Builds

Für völlig getrennte Builds (etwa eine zweite `vite.config.mjs` mit eigener Ausgabe) legen Sie
einen benannten Build an:

```yaml
reprise:
  output_path: "%kernel.project_dir%/public/layout"
  builds:
    landing: "%kernel.project_dir%/public/landing"
```

Dessen Entries erscheinen im Backend als `main (landing)` und werden als `landing:main`
gespeichert. Der Name `_default` ist für `output_path` reserviert.

## 9. Entwicklung, Produktion und Deployment

### Dev-Modus bleibt stehen

`npm run serve` schreibt eine `entrypoints.json` im Dev-Modus (Dateien zeigen auf Port 8080).
Diese Datei bleibt nach dem Beenden des Dev-Servers bestehen – die Seite lädt dann Assets von
einem Server, der nicht mehr läuft. Führen Sie danach immer `npm run build` aus.

### Deployment-Checkliste

1. `npm run build` vor dem Deployment (lokal oder in der CI), damit `public/layout/` mit
   `entrypoints.json` hochgeladen wird.
2. `contao:migrate` auf dem Zielsystem ausführen.
3. **Entrypoints im Seitenlayout der Produktionsdatenbank setzen.** Nach der ersten Migration ist
   das Feld in allen Layouts leer – die Seite hat dann **kein CSS und kein JavaScript**. Entweder
   direkt nach dem Deployment im Backend auswählen oder eine Projekt-Migration verwenden, die das
   automatisch erledigt: [`examples/Migration/DefaultEntrypointMigration.php`](../examples/Migration/DefaultEntrypointMigration.php)
   (nach `src/Migration/` kopieren, Namespace anpassen). Sie setzt nur Layouts, deren Feld `NULL`
   ist; bewusst geleerte Layouts bleiben unverändert.
4. Optional in Produktion `reprise.cache: true` (mit `symfony/cache`).

### Lokale Entwicklung des Bundles (Path-Repository)

Wird das Bundle in einem Projekt unter `packages/` entwickelt:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/*",
            "options": { "versions": { "trassd/contao-reprise-bundle": "1.0.x-dev" } }
        }
    ],
    "require": { "trassd/contao-reprise-bundle": "^1.0@dev" }
}
```

Composer legt in `vendor/` einen **relativen** Symlink an. Er überlebt Deployments per rsync,
solange `packages/` mit hochgeladen wird. Schließen Sie dabei `packages/*/vendor` vom Upload
aus – das ist nur die QA-Umgebung des Bundles.

## 10. Eigene Templates

Die Twig-Funktionen von Reprise bleiben verfügbar, etwa für Sonderseiten ohne Layout oder
Assets, die nicht in jedem Layout gebraucht werden:

```twig
{{ reprise_entry_link_tags('app') }}
{{ reprise_entry_script_tags('app') }}

{# benannter Build #}
{{ reprise_entry_script_tags('main', build: 'landing') }}

{% if reprise_entry_exists('landing') %}…{% endif %}
{% for file in reprise_entry_js_files('app') %}…{% endfor %}
```

Binden Sie einen Entry **nicht** zusätzlich im Layout-Feld und im Template ein – die
Deduplizierung greift zwar für dieselbe Datei, unnötig doppelt konfiguriert ist es trotzdem.

## 11. Fehlerverhalten und Troubleshooting

| Situation | Debug-Modus | Produktion |
|---|---|---|
| Kein Entry gewählt | nichts | nichts |
| Entry fehlt in `entrypoints.json` | Exception | Entry überspringen, Fehler im Contao-Log |
| `entrypoints.json` fehlt/ungültig | Exception | Entry überspringen, Fehler im Contao-Log |
| `reprise.strict_mode: false` | keine Ausgabe für fehlende Entries | keine Ausgabe |

| Symptom | Ursache | Lösung |
|---|---|---|
| Seite ohne CSS/JS, keine Fehler | Im Seitenlayout ist kein Entry gewählt | Entrypoints im Layout wählen ([Abschnitt 7](#7-entrypoints-im-seitenlayout-wählen)) |
| Assets laden von `:8080`, Server läuft nicht | `entrypoints.json` noch im Dev-Modus | `npm run build` |
| Auswahl im Backend ist leer | Noch nicht gebaut oder `output_path` passt nicht zu `outputPath` | Bauen, Pfade abgleichen |
| `Could not find the entrypoints file` | Build fehlt auf dem System | `npm run build` bzw. `public/layout/` mit deployen |
| `Could not find the entry "x"` | Entry umbenannt/entfernt | Layout neu speichern bzw. Entry wählen |
| HMR verbindet nicht | Port 8080 nicht freigegeben oder `hmr` falsch | `.ddev/config.watcher.yaml`, `ddev restart`, `hmr`-Block prüfen |
| CORS-Fehler im Dev-Modus | `server.cors.origin` passt nicht zur Projekt-URL | `cors: { origin: primaryUrl }` |
| Nach dem Build alte Dateien im Cache | HTTP-Cache von Contao | `contao-console cache:clear` |

## 12. Rsbuild statt Vite

Reprise unterstützt auch [Rsbuild](https://rsbuild.dev). Das Bundle funktioniert unverändert, da
es nur die erzeugte `entrypoints.json` liest:

```js
// rsbuild.config.mjs
import { defineConfig } from "@rsbuild/core";
import Symfony from "@symfony/reprise/rsbuild";

export default defineConfig({
	source: { entry: { app: "./layout/app.js" } },
	plugins: [Symfony({ outputPath: "public/layout", publicPath: "/layout/" })],
});
```
