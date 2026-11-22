import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import { lineCrumb, stopCrumb } from "@/components/timetable/breadcrumbs";

export const routes: RouteRecordRaw[] = [
    { path: "/:provider", name: "departures", component: () => import("@/pages/MainPage.vue") },
    {
        // the page's columns: the search or the line page on the left (`side`), a stop's timetable on the right; the
        // pages under it keep the navigation entry active
        path: "/:provider/timetable",
        name: "timetable",
        component: () => import("@/pages/TimetablePage.vue"),
        meta: { breadcrumb: { label: () => "Rozkład", icon: "calendar" } },
        children: [
            {
                path: "stops/:stop",
                name: "timetable-stop",
                component: () => import("@/pages/StopTimetablePage.vue"),
                meta: { breadcrumb: { ...stopCrumb, parent: "timetable" } },
            },
            {
                // the line page alone on the left, its right column waits for a stop
                path: "lines/:line",
                name: "timetable-line",
                components: { side: () => import("@/pages/TimetableLinePage.vue") },
                // the line page shows the track it was left on
                meta: { breadcrumb: { ...lineCrumb, parent: "timetable", query: ["track"] } },
            },
            {
                // the same line page instance stays on the left while its stops are opened
                path: "lines/:line/stops/:stop",
                name: "timetable-line-stop",
                // a line at a stop is under the line's page, its sibling in the router
                meta: { breadcrumb: { ...stopCrumb, parent: "timetable-line" } },
                components: {
                    default: () => import("@/pages/StopTimetablePage.vue"),
                    side: () => import("@/pages/TimetableLinePage.vue"),
                },
            },
        ],
    },
    { path: "/", name: "providers", component: () => import("@/pages/ProviderChooserPage.vue") },
];

export const router = createRouter({
    routes,
    history: createWebHistory(),
});
