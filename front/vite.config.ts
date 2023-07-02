/*
 * Copyright (C) 2022 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { readFileSync } from "fs";
import { VitePWA } from "vite-plugin-pwa";
import SvgIconLoader from "./src/svg-icon-loader";
import viteImagemin from "vite-plugin-imagemin";
import { sentryVitePlugin } from "@sentry/vite-plugin";

const dist = path.resolve(__dirname, "./build/public");

function readSecret(path: string): string | undefined {
    try {
        return readFileSync(path, { encoding: "utf-8" });
    } catch {
        return undefined;
    }
}

const sentryAuthToken =
    readSecret(process.env.SENTRY_AUTH_TOKEN_FILE || "/run/secrets/sentry-auth-token") ||
    process.env.SENTRY_AUTH_TOKEN ||
    false;

export default defineConfig({
    build: {
        outDir: `${dist}/`,
        sourcemap: true,
        manifest: true,
        rollupOptions: {
            input: [path.resolve(__dirname, "./src/app.ts")],
        },
    },
    define: {
        __IS_SSR__: false,
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
    plugins: [
        vue(),
        VitePWA({}),
        viteImagemin(),
        SvgIconLoader({ match: /resources\/icons\/.*\.svg$/ }),
        sentryAuthToken &&
            sentryVitePlugin({
                org: process.env.SENTRY_ORG || "cojedzie",
                project: process.env.SENTRY_PROJECT || "frontend-vue",
                authToken: sentryAuthToken,
                sourcemaps: {
                    assets: `${dist}/**`,
                },
                release: process.env.COJEDZIE_VERSION,
            }),
    ],
    publicDir: path.resolve(__dirname, "./public"),
    resolve: {
        alias: [
            {
                find: "@resources",
                replacement: path.resolve(__dirname, "./resources"),
            },
            {
                find: "@styles",
                replacement: path.resolve(__dirname, "./styles"),
            },
            {
                find: "@",
                replacement: path.resolve(__dirname, "./src"),
            },
        ],
    },
    test: {},
});
