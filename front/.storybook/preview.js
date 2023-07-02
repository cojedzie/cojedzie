import "./setup";

import "../styles/main.scss";

import { setup } from "@storybook/vue3";
import components from "../src/components";
import filters from "../src/filters";
import globals from "../src/globals";
import { install as api } from "../src/api";

import "moment/dist/locale/pl";

setup(app => {
    app.use(components);
    app.use(filters);
    app.use(globals);
    app.use(api);
});

export const parameters = {
    actions: { argTypesRegex: "^on[A-Z].*" },
    controls: {
        matchers: {
            color: /(background|color)$/i,
            date: /Date$/,
        },
    },
};
