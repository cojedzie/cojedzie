import type { StorybookConfig } from "@storybook/vue3-vite";
import { mergeConfig, loadConfigFromFile } from "vite";
import path from "path";

const config: StorybookConfig = {
    stories: ["../src/**/*.mdx", "../src/**/*.stories.@(js|jsx|ts|tsx)"],
    addons: ["@storybook/addon-docs"],
    framework: {
        name: "@storybook/vue3-vite",
        options: {},
    },
    typescript: {
        check: false,
    },
    async viteFinal(storybookConfig) {
        const { config } = await loadConfigFromFile(
            { command: "serve", mode: "development" },
            path.resolve(import.meta.dirname, "../vite.config.ts")
        );

        // remove vue-docgen plugin as it causes problems, and the PWA plugin, as storybook needs no service worker
        storybookConfig.plugins = storybookConfig.plugins
            .flat()
            .filter(
                plugin =>
                    plugin &&
                    plugin.name !== "storybook:vue-docgen-plugin" &&
                    !plugin.name?.startsWith("vite-plugin-pwa")
            );

        // return the customized config
        return mergeConfig(storybookConfig, {
            resolve: config.resolve,
            // has-symbols requires global to be available
            define: {
                ...config.define,
                global: {},
                process: {
                    env: Object.fromEntries(Object.entries(process.env).filter(([key, _]) => /COJEDZIE_/.test(key))),
                },
            },
            // storybook brings its own vue plugin
            plugins: config.plugins
                .flat()
                .filter(plugin => plugin && plugin.name !== "vite:vue" && !plugin.name?.startsWith("vite-plugin-pwa")),
        });
    },
};

export default config;
