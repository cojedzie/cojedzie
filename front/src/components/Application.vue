<template>
    <app-header />
    <main ref="main" class="flex" :class="{ 'app-main--bottom-bar': provider }">
        <!-- keyed by the provider, so a switch mounts the page anew and it loads the new provider -->
        <router-view :key="provider ?? ''" />
    </main>
    <app-footer />
    <app-bottom-bar v-if="provider" :provider="provider" />
</template>

<script setup lang="ts">
import { computed, onMounted, useTemplateRef } from "vue";
import { useRoute } from "vue-router";
import AppBottomBar from "@/components/app/AppBottomBar.vue";
import AppFooter from "@/components/app/AppFooter.vue";
import AppHeader from "@/components/app/AppHeader.vue";

const main = useTemplateRef<HTMLElement>("main");
const route = useRoute();

const provider = computed(() => route.params.provider as string | undefined);

onMounted(() => {
    main.value.classList.remove("not-ready");
});
</script>

<style lang="scss">
@import "../../styles/_variables.scss";

.app-main--bottom-bar {
    // the fixed bottom bar covers the end of the page otherwise
    @include media-breakpoint-down("sm") {
        padding-bottom: calc(var(--app-bottom-bar-height) + env(safe-area-inset-bottom));
    }
}
</style>
