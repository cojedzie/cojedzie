/*
 * Copyright (C) 2026 Kacper Donat
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

import type { Plugin } from "vite";

const fs = require("fs");
const path = require("path");

type StaticImagesOptions = {
    source: string;
    target: string;
};

const contentTypes = {
    ".png": "image/png",
    ".jpg": "image/jpeg",
    ".svg": "image/svg+xml",
    ".ico": "image/x-icon",
};

// only images referenced by url, sources (.ai, .kra) and subdirectories (imported by components) are skipped
function listImages(source: string): string[] {
    return fs
        .readdirSync(source, { withFileTypes: true })
        .filter(entry => entry.isFile() && path.extname(entry.name) in contentTypes)
        .map(entry => entry.name);
}

export default function ({ source, target }: StaticImagesOptions): Plugin {
    return {
        name: "static-images",
        configureServer(server) {
            server.middlewares.use(`/${target}`, (req, res, next) => {
                const name = decodeURIComponent((req.url || "").split("?")[0].replace(/^\//, ""));

                if (!listImages(source).includes(name)) {
                    return next();
                }

                res.setHeader("Content-Type", contentTypes[path.extname(name)]);
                fs.createReadStream(path.join(source, name)).pipe(res);
            });
        },
        generateBundle() {
            for (const name of listImages(source)) {
                this.emitFile({
                    type: "asset",
                    fileName: `${target}/${name}`,
                    source: fs.readFileSync(path.join(source, name)),
                });
            }
        },
    };
}
