window.global = this;

window.CoJedzie = {
    maptiler: {
        key: process.env.COJEDZIE_MAPTILER_KEY,
    },
    api: {
        base: process.env.COJEDZIE_API,
        hub: process.env.COJEDZIE_API_HUB || process.env.COJEDZIE_API,
    },
};
