# Contao Reprise Bundle

Integrates [symfony/reprise](https://github.com/symfony/reprise) (Vite/Rsbuild) into Contao 5.7+:
select the Vite entrypoints per page layout in the back end, and their stylesheets and scripts are
added to the page `<head>` automatically.

📖 **Detailed guide (German):** [docs/anleitung.md](docs/anleitung.md) · **Examples:** [examples/](examples)

## Features

- Registers `RepriseBundle` via the Contao Manager plugin – no project plugin needed.
- New field **Vite entrypoints** in page layouts, for classic (`fe_page`) and modern (`page/layout`) layouts.
- Stylesheets first, then scripts, in the selected order; shared chunks are included only once.
- Vite dev server support: the HMR client (`@vite/client`) is injected automatically, exactly once.
- Multiple Reprise builds (`reprise.builds`), stored as `<build>:<entry>`.
- Debug mode fails loudly on missing builds/entries; production skips them and logs to the Contao error log.

## Requirements

- PHP 8.4, Contao 5.7
- Vite ≥ 8.2 (or Rsbuild) with the npm package `@symfony/reprise` ^1.3

## Quick start

1. Install the bundle and update the database:

   ```bash
   composer require trassd/contao-reprise-bundle
   vendor/bin/contao-console contao:migrate
   ```

2. Point Reprise to the build output (`config/config.yaml`):

   ```yaml
   reprise:
     output_path: "%kernel.project_dir%/public/layout"
   ```

3. Configure Vite (`vite.config.mjs`):

   ```js
   import { defineConfig } from "vite";
   import Symfony from "@symfony/reprise/vite";

   export default defineConfig({
   	input: { app: "./layout/app.js" },
   	plugins: [Symfony({ outputPath: "public/layout", publicPath: "/layout/" })],
   });
   ```

4. Build: `npm install --save-dev vite @symfony/reprise && npx vite build`

5. In **Layout → Themes → Page layouts**, select `app` in **Vite entrypoints**.

For the DDEV dev server (HMR over HTTPS), SRI, multiple builds and deployment, see the
[guide](docs/anleitung.md) and the [examples](examples).

## Examples

| File | Purpose |
|---|---|
| [`examples/vite.config.mjs`](examples/vite.config.mjs) | Full setup for DDEV: dev server, HMR, full reload on template changes, SRI |
| [`examples/vite.config.minimal.mjs`](examples/vite.config.minimal.mjs) | Minimal build configuration |
| [`examples/package.json`](examples/package.json) | npm scripts (`serve`, `watch`, `build`) and dependencies |
| [`examples/config/config.yaml`](examples/config/config.yaml) | All Reprise options with comments |
| [`examples/.ddev/config.watcher.yaml`](examples/.ddev/config.watcher.yaml) | Exposes the Vite dev server port in DDEV |
| [`examples/layout/app.js`](examples/layout/app.js) | Minimal entry |
| [`examples/Migration/DefaultEntrypointMigration.php`](examples/Migration/DefaultEntrypointMigration.php) | Project migration that selects `app` in all layouts after the first deployment |

## Entrypoint values

| Build | Stored value | Shown in the back end |
|---|---|---|
| default (`reprise.output_path`) | `app` | `app` |
| named (`reprise.builds.landing`) | `landing:main` | `main (landing)` |

## Errors

| Situation | Debug mode | Production |
|---|---|---|
| Entry missing from `entrypoints.json` | exception | skipped, logged |
| `entrypoints.json` missing or invalid | exception | skipped, logged |
| `reprise.strict_mode: false` | renders nothing for missing entries | renders nothing |

> **Deployment:** after the first `contao:migrate`, the field is empty in every layout, so pages have
> no CSS/JS until entrypoints are selected – use the example migration or select them right after deploying.

The Twig functions `reprise_entry_link_tags()` / `reprise_entry_script_tags()` remain available for custom templates.

## Development

```bash
composer install
composer all        # ECS, PHPStan, PHPUnit, Infection
composer mutation   # mutation tests only
```

Mutation tests run with [Infection](https://infection.github.io) and require a covered-code MSI of 100 %.
The script loads Xdebug only for the coverage run (`-d zend_extension=xdebug.so`), so it works in DDEV
without enabling Xdebug globally. Equivalent mutants are ignored in `infection.json5` with a comment
explaining why.

## License

MIT
