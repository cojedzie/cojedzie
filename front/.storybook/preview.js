import "./setup";

import "../styles/tailwind.css";
import "../styles/global/storybook.css";

import { setup } from "@storybook/vue3-vite";
import { createPinia } from "pinia";
import { createMemoryHistory, createRouter } from "vue-router";
import filters from "../src/filters";
import { install as api } from "../src/api";

// shell components link to the named routes, stories render them inside a provider's page
const page = { render: () => null };
const router = createRouter({
    history: createMemoryHistory(),
    routes: [
        { path: "/", name: "providers", component: page },
        { path: "/:provider", name: "departures", component: page },
        { path: "/:provider/timetable", name: "timetable", component: page },
    ],
});
router.push("/trojmiasto");

setup(app => {
    app.use(filters);
    app.use(api);
    app.use(createPinia());
    app.use(router);
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
