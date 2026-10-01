// vite.config.mjs — minimal setup without a dev server proxy (e.g. build/watch only)
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
