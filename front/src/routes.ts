import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    { path: "/:provider", component: () => import("@/pages/MainPage.vue") },
    { path: "/", component: () => import("@/pages/ProviderChooserPage.vue") },
];

export const router = createRouter({
    routes,
    history: createWebHistory(),
});
