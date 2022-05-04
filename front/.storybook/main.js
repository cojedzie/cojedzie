const createWebpackConfig = require('../webpack.config.js');

__webpack_public_path__ = '/storybook/'

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
    env: (env) => ({
        ...env,
        APP_MAPTILER_KEY: process.env.APP_MAPTILER_KEY,
    }),
    webpackFinal: async (config) => {
        const customConfig = createWebpackConfig('development', { ...process.argv, mode: 'development' });

        return {
            ...config,
            entry: config.entry.map(entry => {
                return entry.includes("webpack-hot-middleware")
                    ? entry + '&path=/storybook/__webpack_hmr'
                    : entry;
            }),
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
