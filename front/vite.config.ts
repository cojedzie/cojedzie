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

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { VitePWA } from "vite-plugin-pwa";
import SvgIconLoader from "./src/svg-icon-loader";
import viteImagemin from "vite-plugin-imagemin"

export default defineConfig({
    plugins: [
        vue(),
        VitePWA({}),
        viteImagemin(),
        SvgIconLoader({ match: /resources\/icons\/.*\.svg$/ }),
    ],
    publicDir: path.resolve(__dirname, './public'),
    build: {
        outDir: path.resolve(__dirname, './build/public/'),
        manifest: true,
        rollupOptions: {
            input: [
                path.resolve(__dirname, './src/app.ts'),
                path.resolve(__dirname, './styles/main.scss'),
            ]
        }
    },
    resolve: {
        alias: [
            {
                find: '@resources',
                replacement: path.resolve(__dirname, "./resources")
            },
            {
                find: '@styles',
                replacement: path.resolve(__dirname, "./styles")
            },
            {
                find: '@',
                replacement: path.resolve(__dirname, "./src")
            },
        ]
    },
    define: {
        __IS_SSR__: false,
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
})
