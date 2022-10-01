const { mergeConfig, loadConfigFromFile } = require("vite");
const path = require("path");

module.exports = {
    "stories": [
        "../src/**/*.stories.mdx",
        "../src/**/*.stories.@(js|jsx|ts|tsx)"
    ],
    "addons": [
        "@storybook/addon-links",
        "@storybook/addon-essentials"
    ],
    "framework": "@storybook/vue3",
    "core": {
        "builder": "@storybook/builder-vite"
    },
    typescript: {
        check: false
    },
    async viteFinal(storybookConfig, { configType }) {
        const { config } = await loadConfigFromFile(path.resolve(__dirname, "../vite.config.ts"))

        // remove vue-docgen plugin as it causes problems
        storybookConfig.plugins = storybookConfig.plugins.filter(plugin => plugin.name !== 'vue-docgen')

        // return the customized config
        return mergeConfig(storybookConfig, {
            base: '/storybook/dist/',
            resolve: config.resolve,
            // has-symbols requires global to be available
            define: {
                ...config.define,
                global: {},
                process: {
                    env: Object.fromEntries(
                        Object
                            .entries(process.env)
                            .filter(([key, _]) => /(COJEDZIE|APP)_/.test(key))
                    )
                }
            },
            // remove duplicated vue plugin
            plugins: config.plugins.filter(plugin => plugin.name !== 'vite:vue'),
        });
    },
}
