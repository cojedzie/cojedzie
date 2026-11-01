<template>
    <!-- the tabs in the middle of the bar whatever the width of Więcej at its end -->
    <nav
        v-responsive
        class="app-bottom-bar fixed inset-x-0 bottom-0 grid grid-cols-[1fr_auto_1fr] items-center px-2 md:hidden"
    >
        <div class="col-start-2 flex gap-2">
            <router-link
                v-for="entry in tabs"
                :key="entry.id"
                :to="{ name: entry.route, params: { provider } }"
                class="app-bottom-bar__tab flex items-center justify-center gap-2 px-5 py-1.5 text-sm"
                exact-active-class="app-bottom-bar__tab--active"
                :aria-label="entry.label"
                :title="entry.label"
            >
                <ui-icon :icon="entry.icon" class="text-lg" />
                <span class="app-bottom-bar__label">{{ entry.label }}</span>
            </router-link>
        </div>
        <button
            class="app-bottom-bar__tab app-bottom-bar__more flex items-center justify-center justify-self-end px-5 py-1.5"
            :aria-expanded="moreOpen ? 'true' : 'false'"
            aria-haspopup="dialog"
            aria-label="Więcej"
            @click="moreOpen = !moreOpen"
        >
            <ui-icon icon="more" class="text-lg" />
        </button>
        <ui-sheet v-if="moreOpen" @leave="moreOpen = false">
            <app-more-menu :entries="rest" :provider="provider" @navigate="moreOpen = false" />
        </ui-sheet>
    </nav>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { navigation } from "@/navigation";
import AppMoreMenu from "@/components/app/AppMoreMenu.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiSheet from "@/components/ui/UiSheet.vue";

defineOptions({ name: "AppBottomBar" });

defineProps<{ provider: string }>();

const tabs = navigation.filter(entry => entry.bottomBar);
const rest = navigation.filter(entry => !entry.bottomBar);

const moreOpen = ref(false);
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.app-bottom-bar {
    z-index: $app-bar-z-index;
    height: calc(var(--app-bottom-bar-height) + env(safe-area-inset-bottom));
    padding-bottom: env(safe-area-inset-bottom);
    background: $white;
    box-shadow: 0 -1px 3px $app-bar-shadow-color;

    &__tab {
        color: $text-muted;

        // before the pill states, so the active one stays white on hover
        &:hover {
            color: $body-color;
        }

        @include app-nav-item;
    }

    // a narrow bar names only the current tab, v-responsive tells how wide it is
    &:not(.size-sm) &__tab:not(&__tab--active) &__label {
        display: none;
    }
}
</style>
