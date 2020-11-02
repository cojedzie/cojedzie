import Vue from "vue";
import Component from "vue-class-component";
import VueRouter, { RouteConfig } from "vue-router";
import { Main, ProviderChooser } from "@/components";
import store from "@/store";

const routes: RouteConfig[] = [
    { path: "/:provider", component: Main },
    { path: "/", component: ProviderChooser },
]

export const router = new VueRouter({
    routes,
    mode: 'history',
});

@Component({ template: require("@templates/app.html"), router, store })
export class Application extends Vue {

}
