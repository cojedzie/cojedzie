const createWebpackConfig = require('../webpack.config.js');
const { exit } = require("yargs");

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
        "builder": "@storybook/builder-webpack5"
    },
    typescript: {
        check: false
    },
    webpackFinal: async (config) => {
        const customConfig = createWebpackConfig('development', { ...process.argv, mode: 'development' });

        return {
            ...config,
            resolve: {
                ...config.resolve,
                alias: {
                    ...config.resolve.alias,
                    ...customConfig.resolve.alias,
                }
            },
            module: {
                ...config.module,
                rules: [
                    ...customConfig.module.rules,
                    config.module.rules[6],
                    config.module.rules[7],
                    config.module.rules[8],
                ]
            }
        };
    },
}
