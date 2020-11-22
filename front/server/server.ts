import express from 'express';
import path from "path";
import fs from "fs";
import request from "request";

const server = express();

const port = parseInt(process.env.APP_PORT) || 3000;
const host = process.env.APP_HOST || '0.0.0.0';
const api  = process.env.APP_API || "https://cojedzie.pl/api";

const gtm_tracking = process.env.APP_GTM || '';
const version = "2020.11-dev";

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

    request.get(`${api}/v1/providers/${provider}`, (err, _, body) => {
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
    })
})

server.listen(port, host, () => {
    console.info(`Server started at ${host}:${port}`);
});

process.on('SIGINT', function() {
    console.info("Terminating server...");
    process.exit();
});
