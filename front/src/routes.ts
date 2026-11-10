import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";

export const routes: RouteRecordRaw[] = [
    { path: "/:provider", name: "departures", component: () => import("@/pages/MainPage.vue") },
    {
        // the page's columns: the search or the line page on the left (`side`), a stop's timetable on the right; the
        // pages under it keep the navigation entry active
        path: "/:provider/timetable",
        name: "timetable",
        component: () => import("@/pages/TimetablePage.vue"),
        children: [
            {
                path: "stops/:stop",
                name: "timetable-stop",
                component: () => import("@/pages/StopTimetablePage.vue"),
            },
            {
                path: "lines/:line/stops/:stop",
                name: "timetable-line-stop",
                component: () => import("@/pages/StopTimetablePage.vue"),
            },
        ],
    },
    { path: "/", name: "providers", component: () => import("@/pages/ProviderChooserPage.vue") },
];

export const router = createRouter({
    routes,
    history: createWebHistory(),
});
