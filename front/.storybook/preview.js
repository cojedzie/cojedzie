import "./setup";

import "../styles/tailwind.css";
import "../styles/main.scss";

import { setup } from "@storybook/vue3-vite";
import filters from "../src/filters";
import { install as api } from "../src/api";

import "moment/dist/locale/pl";

setup(app => {
    app.use(filters);
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
export const tags = ["autodocs"];
