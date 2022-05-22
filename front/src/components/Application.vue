<template>
    <main class="d-flex">
        <router-view />
    </main>
</template>

<script lang="ts">
import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import store from "@/store";
import { Options, Vue } from "vue-class-component";
import { createApp } from "vue";

const routes: RouteRecordRaw[] = [
    { path: "/:provider", component: () => import ("@/pages/MainPage.vue") },
    { path: "/", component: () => import ("@/pages/ProviderChooserPage.vue") },
]

export const router = createRouter({
    routes,
    history: createWebHistory(),
});

@Options({ router, store })
export default class Application extends Vue {
    mounted() {
        this.$el.classList.remove('not-ready');
    }
}

export const app = createApp(Application);

app.use(router);
</script>
