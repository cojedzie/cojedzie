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

import express from 'express';
import path from "path";
import fs from "fs";
import request from "request";

const { version } = require("../package.json")

const server = express();

const port = parseInt(process.env.APP_PORT) || 3000;
const host = process.env.APP_HOST || '0.0.0.0';
const api  = process.env.APP_API || "https://cojedzie.pl";
const dev  = process.env.APP_MODE === 'development';

const gtm_tracking = process.env.APP_GTM || '';

const manifest = JSON.parse(
    fs.readFileSync(path.join(__dirname, "../resources/manifest.json")).toString("utf-8")
);

const provider_manifests = {}

function generateProviderManifest(provider: any) {
    return {
        ...manifest,
        start_url: `/${provider.id}`,
        name: `${manifest.name} - ${provider.name}`,
        short_name: `${manifest.short_name} - ${provider.shortName}`,
    };
}

server.set("views", path.join(__dirname, "../resources/views/"));
server.set("view engine", "ejs");

server.use(express.static(path.join(__dirname, "../build/public/")))

if (dev) {
  const webpack = require('webpack')
  const webpackDevMiddleware = require('webpack-dev-middleware')
  const webpackHotMiddleware = require('webpack-hot-middleware')

  const config = require('../webpack.config.js')('development', { mode: 'development' });
  const compiler = webpack(config);
  const instance = webpackDevMiddleware(compiler);

  server.use(instance);
  server.use(webpackHotMiddleware(compiler));

  server.get('/service-worker.js', (req, res) => {
      const content = instance.context.outputFileSystem.readFileSync(path.join(__dirname, '../build/public/service-worker.js'));
      res.set('Content-Type', 'application/javascript');
      res.send(content);
  })
}

server.get("/:provider?/manifest.json", (req, res) => {
    const provider = req.params.provider;

    if (typeof provider === "undefined") {
        res.send(manifest);
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
        } catch(error) {
            console.error(`Problem with generating manifest for ${provider}: ${error.message}`);
            res.send(manifest);
        }
    })
})

server.get("/:provider?/*", (req, res) => {
    const manifest_path = req.params.provider
        ? `/${req.params.provider}/manifest.json`
        : "/manifest.json";

    const year = (new Date()).getFullYear();

    res.render("index", {
        manifest_path,
        gtm_tracking,
        version,
        year,
        config: { api },
    })
})

server.listen(port, host, () => {
    console.info(`Server started at ${host}:${port}`);
});

process.on('SIGINT', function() {
    console.info("Terminating server...");
    process.exit();
});
