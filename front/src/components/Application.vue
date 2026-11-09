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
import { provideDisplayTimeZone } from "@/composables/useDisplayTimeZone";
import { provideToday, useTodayIn } from "@/composables/useToday";
import { useSelectionStore } from "@/stores/selection";
import { Temporal } from "@/time";

const main = useTemplateRef<HTMLElement>("main");
const route = useRoute();

const provider = computed(() => route.params.provider as string | undefined);

// dates the app shows are the provider's, so is today
const selection = useSelectionStore();
provideToday(useTodayIn(() => selection.provider?.timezone));
// clock times are shown in the provider's zone too, where a rider's preference would go
provideDisplayTimeZone(computed(() => selection.provider?.timezone ?? Temporal.Now.timeZoneId()));

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
