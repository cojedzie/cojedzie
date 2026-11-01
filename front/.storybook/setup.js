window.global = this;

window.CoJedzie = {
    version: "storybook",
    production: false,
    maptiler: {
        key: process.env.COJEDZIE_MAPTILER_KEY,
    },
    api: {
        base: process.env.COJEDZIE_API,
        hub: process.env.COJEDZIE_API_HUB || process.env.COJEDZIE_API,
    },
};
