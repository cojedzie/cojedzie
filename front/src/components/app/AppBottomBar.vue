<template>
    <!-- the tabs in the middle of the bar whatever the width of Więcej at its end -->
    <nav v-responsive class="bottom-bar">
        <div class="tabs">
            <router-link
                v-for="entry in tabs"
                :key="entry.id"
                :to="{ name: entry.route, params: { provider } }"
                class="tab"
                active-class="active"
                :aria-label="entry.label"
                :title="entry.label"
            >
                <ui-icon :icon="entry.icon" class="tab-icon" />
                <span class="tab-name">{{ entry.label }}</span>
            </router-link>
        </div>
        <button
            class="tab more"
            :aria-expanded="moreOpen ? 'true' : 'false'"
            aria-haspopup="dialog"
            aria-label="Więcej"
            @click="moreOpen = !moreOpen"
        >
            <ui-icon icon="more" class="tab-icon" />
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

<style scoped>
@reference "@styles/tailwind.css";

.bottom-bar {
    position: fixed;
    inset-inline: 0;
    inset-block-end: 0;
    z-index: var(--z-index-bar);
    /* the tabs in the middle of the bar whatever the width of Więcej at its end */
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    block-size: calc(var(--app-bottom-bar-height) + env(safe-area-inset-bottom));
    padding-inline: --spacing(2);
    padding-block-end: env(safe-area-inset-bottom);
    background: white;
    box-shadow: var(--shadow-bar-top);

    @variant md {
        display: none;
    }

    & .tabs {
        grid-column-start: 2;
        display: flex;
        gap: --spacing(2);
    }

    & .tab {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: --spacing(2);
        padding: --spacing(1.5) --spacing(5);
        color: var(--color-muted);
        font-size: var(--text-sm);

        /* before the pill states, so the active one stays white on hover */
        &:hover {
            color: var(--color-body);
        }

        @apply app-nav-item;

        &.active {
            @apply app-nav-item-active;
        }
    }

    & .more {
        justify-self: end;
    }

    & .tab-icon {
        font-size: var(--text-lg);
    }

    /* a narrow bar names only the current tab, v-responsive tells how wide it is */
    &:not(.size-sm) .tab:not(.active) .tab-name {
        display: none;
    }
}
</style>
