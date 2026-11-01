import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";

export const routes: RouteRecordRaw[] = [
    { path: "/:provider", name: "departures", component: () => import("@/pages/MainPage.vue") },
    { path: "/", name: "providers", component: () => import("@/pages/ProviderChooserPage.vue") },
];

export const router = createRouter({
    routes,
    history: createWebHistory(),
});
