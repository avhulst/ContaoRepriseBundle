// vite.config.mjs — Vite + Reprise for a Contao project running in DDEV
//
// Requires Vite >= 8.2 for the top-level "input" option. On Vite 8.1 and older,
// move the entries to "build.rollupOptions.input".
import { defineConfig } from "vite";
import Symfony from "@symfony/reprise/vite";

// Where the build goes. Must match "reprise.output_path" in config/config.yaml.
const outputPath = "public/layout";
const publicPath = "/layout/";

// Public URL of the project (set by DDEV inside the web container)
const primaryUrl = process.env.DDEV_PRIMARY_URL || "https://example.ddev.site";

// The dev server is reached through the port exposed in .ddev/config.watcher.yaml
const devPort = 8080;
const devServerOrigin = `${primaryUrl}:${devPort}`;

// Full page reload when server-rendered files change (Twig templates, DCA, config …)
const reloadOn = ["config", "contao", "src", "templates", "translations"];

const fullReload = {
	name: "full-reload",
	configureServer(server) {
		server.watcher.add(reloadOn.map((dir) => `${process.cwd()}/${dir}`));
		server.watcher.on("change", (file) => {
			if (reloadOn.some((dir) => file.startsWith(`${process.cwd()}/${dir}/`))) {
				server.ws.send({ type: "full-reload" });
			}
		});
	},
};

export default defineConfig(({ command }) => ({
	// Entry name => file. The names ("app", "landing") are what you select in the page layout.
	input: {
		app: "./layout/app.js",
		// landing: "./layout/landing.js",
	},
	plugins: [
		Symfony({
			outputPath,
			publicPath,
			devServerOrigin,
			// Subresource Integrity for production builds (optional)
			integrity: { enabled: command === "build", algorithms: ["sha384"] },
			// Copy static files and register them in manifest.json for {{ asset('layout/images/…') }} (optional)
			// copy: [{ from: "layout/images", to: "images" }],
		}),
		fullReload,
	],
	build: {
		sourcemap: true,
		// Empties public/layout before each build: keep nothing else in that directory
		emptyOutDir: true,
	},
	server: {
		host: "0.0.0.0",
		port: devPort,
		strictPort: true,
		allowedHosts: true,
		cors: { origin: primaryUrl },
		hmr: {
			protocol: "wss",
			host: new URL(primaryUrl).hostname,
			clientPort: devPort,
		},
	},
}));
