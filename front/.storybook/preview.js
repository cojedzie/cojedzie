import "../styles/main.scss"

window.CoJedzie = {
    maptiler: {
        key: process.env.APP_MAPTILER_KEY
    }
}

export const parameters = {
  actions: { argTypesRegex: "^on[A-Z].*" },
  controls: {
    matchers: {
      color: /(background|color)$/i,
      date: /Date$/,
    },
  },
}
