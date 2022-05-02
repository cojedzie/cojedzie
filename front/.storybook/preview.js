import "../styles/main.scss"

import { app } from "@storybook/vue3"
import components from "../src/components"
import filters from "../src/filters"
import globals from "../src/globals"

app.use(components)
app.use(filters)
app.use(globals)

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
