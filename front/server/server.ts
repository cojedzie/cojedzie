/*
 * Copyright (C) 2021 Kacper Donat
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

import express, { Express, Request, Response } from 'express';
import path from "path";
import fs from "fs";
import request from "request";

const { version } = require("../package.json")

const port = parseInt(process.env.APP_PORT) || 3000;
const host = process.env.APP_HOST || '0.0.0.0';
const api  = process.env.APP_API || "https://cojedzie.pl";
const dev  = process.env.APP_MODE === 'development';

const gtm_tracking = process.env.APP_GTM || '';
const maptiler_key = process.env.APP_MAPTILER_KEY || "unknown";

const web_manifest = JSON.parse(
    fs.readFileSync(path.join(__dirname, "../resources/manifest.json")).toString("utf-8")
);

const assets_manifest = JSON.parse(
    fs.readFileSync(path.join(__dirname, "../build/public/manifest.json")).toString("utf-8")
);

const provider_manifests = {}

function generateProviderManifest(provider: any) {
    return {
        ...web_manifest,
        start_url: `/${provider.id}`,
        name: `${web_manifest.name} - ${provider.name}`,
        short_name: `${web_manifest.short_name} - ${provider.shortName}`,
    };
}

const renderPageAction = (callback?: ({ req: Request, res: Response, err: Error, html: string }) => void) => (req: Request, res: Response) => {
    const manifest_path = req.params.provider
        ? `/${req.params.provider}/manifest.json`
        : "/manifest.json";

    const year = (new Date()).getFullYear();

    res.render("index", {
        manifest_path,
        gtm_tracking,
        version,
        year,
        config: {
            version,
            api,
            maptiler: {
                key: maptiler_key
            }
        },
        is_production: !dev,
        manifest: assets_manifest,
    }, (err, html) => callback ? callback({ req, res, err, html }) : res.send(html))
}

const getWebManifestAction = (req: Request, res: Response) => {
    const provider = req.params.provider;

    if (typeof provider === "undefined") {
        res.send(web_manifest);
        return;
    }

    if (typeof provider_manifests[provider] !== "undefined") {
        res.send(provider_manifests[provider]);
        return;
    }

    console.log(`No manifest entry for ${provider}, calling ${api}/providers/${provider}`);

    request.get(`${api}/api/v1/providers/${provider}`, (err, _, body) => {
        try {
            const info = JSON.parse(body);
            provider_manifests[provider] = generateProviderManifest(info);

            console.info(`Generated manifest for ${provider}`, provider_manifests[provider]);

            res.send(provider_manifests[provider]);
        } catch (error) {
            console.error(`Problem with generating manifest for ${provider}: ${error.message}`);
            res.send(web_manifest);
        }
    })
}

async function createServer(): Promise<Express> {
    const server = express();

    server.set("views", path.join(__dirname, "../resources/views/"));
    server.set("view engine", "ejs");

    server.use(express.static(path.join(__dirname, "../build/public/")))

    server.get("/:provider?/manifest.json", getWebManifestAction)

    if (dev) {
        const { createSever: createViteServer } = require("vite")

        const vite = await createViteServer({
            server: {
                middlewareMode: 'ssr',
                hmr: {
                    port: 3001
                }
            }
        })

        server.use(vite.middlewares)

        server.get("/:provider?/*", renderPageAction(async ({ req, res, html }) => {
            res.send(
                await vite.transformIndexHtml(req.originalUrl, html)
            )
        }))
    } else {
        server.get("/:provider?/*", renderPageAction())
    }

    return server;
}

createServer().then(server => {
    server.listen(port, host, () => {
        console.info(`Server started at ${host}:${port}`);
    });

    process.on('SIGINT', function() {
        console.info("Terminating server...");
        process.exit();
    });
})
