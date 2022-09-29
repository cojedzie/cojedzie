window.global = this

window.CoJedzie = {
    maptiler: {
        key: process.env.APP_MAPTILER_KEY
    },
    api: {
        base: process.env.APP_API_BASE_URL,
        hub: process.env.APP_API_HUB_URL || process.env.APP_API_BASE_URL
    }
}
