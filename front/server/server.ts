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
import type { SentryConfig } from "../src/composables/useAppConfig";
import { matchesUA } from "browserslist-useragent";

const { version: versionFromPackageJson } = require("../../package.json")

const version = process.env.COJEDZIE_VERSION || `v${ versionFromPackageJson }`;
const port = parseInt(process.env.COJEDZIE_PORT) || 3000;
const host = process.env.COJEDZIE_HOST || '0.0.0.0';
const api = process.env.COJEDZIE_API || "https://cojedzie.pl";
const dev = process.env.COJEDZIE_MODE === 'development';

const gtm_tracking = process.env.COJEDZIE_GTM || '';
const maptiler_key = process.env.COJEDZIE_MAPTILER_KEY || "unknown";

const web_manifest = JSON.parse(
    fs.readFileSync(path.join(__dirname, "../../resources/manifest.json")).toString("utf-8")
);

const assets_manifest = dev ? {} : JSON.parse(
    fs.readFileSync(path.join(__dirname, "../public/manifest.json")).toString("utf-8")
);

const provider_manifests = {}

type SentryBrowserSettings = {
    browsers?: string[],
    multiplier?: number,
    tags?: { [tag: string]: string },
}

const sentryBrowserSettings: SentryBrowserSettings[] = [
    {
        browsers: ["unreleased versions"],
        multiplier: 0.7,
        tags: {
            'browser.release-group': 'unreleased'
        }
    },
    {
        browsers: ["defaults"],
        multiplier: 1,
        tags: {
            'browser.release-group': 'current'
        }
    },
    {
        browsers: ["last 1 year"],
        multiplier: 0.8,
        tags: {
            'browser.release-group': 'up to 1 year old'
        }
    },
    {
        browsers: ["last 2 years"],
        multiplier: 0.5,
        tags: {
            'browser.release-group': 'up to 2 years old'
        }
    },
    {
        multiplier: 0.0,
        tags: {
            'browser.release-group': 'old'
        }
    },
]

function generateProviderManifest(provider: any) {
    return {
        ...web_manifest,
        start_url: `/${ provider.id }`,
        name: `${ web_manifest.name } - ${ provider.name }`,
        short_name: `${ web_manifest.short_name } - ${ provider.shortName }`,
    };
}

function computeSentryMultiplierForRequest(req: Request): SentryBrowserSettings|null {
    const ua = req.header('User-Agent');

    for (const settings of sentryBrowserSettings) {
        const { browsers } = settings;

        // settings should be returned if there is no browsers query (match-all) or UA matches browser query
        if (!browsers || matchesUA(ua, { browsers, allowHigherVersions: true })) {
            return settings;
        }
    }

    return null;
}

function generateSentryConfig(req: Request): SentryConfig {
    const replaysSessionSampleRate = parseFloat(process.env.SENTRY_SESSION_REPLAY_RATE || "0.0"),
        replaysErrorSampleRate = parseFloat(process.env.SENTRY_ERROR_REPLAY_RATE || "1.0"),
        tracesSampleRate = parseFloat(process.env.SENTRY_SAMPLE_RATE || "0.05");

    const {
        multiplier = 0,
        tags = {}
    } = computeSentryMultiplierForRequest(req);

    return {
        dsn: process.env.SENTRY_DSN || "",
        environment: process.env.SENTRY_ENVIRONMENT || "",
        replaysErrorSampleRate: replaysErrorSampleRate * multiplier,
        replaysSessionSampleRate: replaysSessionSampleRate * multiplier,
        tracesSampleRate: tracesSampleRate * multiplier,
        tags
    }
}

const renderPageAction = (callback?: (args: { req: Request, res: Response, err: Error, html: string }) => void) => (req: Request, res: Response) => {
    const manifest_path = req.params.provider
        ? `/${ req.params.provider }/manifest.json`
        : "/manifest.json";

    const year = (new Date()).getFullYear();

    res.render("index", {
        manifest_path,
        gtm_tracking,
        version,
        year,
        config: {
            version,
            api: {
                base: process.env.COJEDZIE_API_PUBLIC || process.env.COJEDZIE_API_HUB || process.env.COJEDZIE_API || "https://cojedzie.pl",
                hub: process.env.COJEDZIE_API_HUB || process.env.COJEDZIE_API || "https://cojedzie.pl",
            },
            maptiler: {
                key: maptiler_key
            },
            sentry: generateSentryConfig(req)
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

    console.log(`No manifest entry for ${ provider }, calling ${ api }/providers/${ provider }`);

    request.get(`${ api }/api/v1/providers/${ provider }`, (err, _, body) => {
        try {
            const info = JSON.parse(body);
            provider_manifests[provider] = generateProviderManifest(info);

            console.info(`Generated manifest for ${ provider }`, provider_manifests[provider]);

            res.send(provider_manifests[provider]);
        } catch (error) {
            console.error(`Problem with generating manifest for ${ provider }: ${ error.message }`);
            res.send(web_manifest);
        }
    })
}

async function createServer(): Promise<Express> {
    const server = express();

    server.set("views", path.join(__dirname, "../../resources/views/"));
    server.set("view engine", "ejs");

    server.use(express.static(path.join(__dirname, "../public/")))

    server.get("/:provider?/manifest.json", getWebManifestAction)

    if (dev) {
        const { createServer: createViteServer } = require("vite")

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
        console.info(`Server started at ${ host }:${ port }`);
    });

    process.on('SIGINT', function () {
        console.info("Terminating server...");
        process.exit();
    });
})
