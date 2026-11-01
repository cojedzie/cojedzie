<template>
    <nav class="app-more-menu min-w-48">
        <ui-menu v-if="entries.length > 0" class="app-more-menu__entries mb-2 pb-2">
            <ui-menu-item
                v-for="entry in entries"
                :key="entry.id"
                tag="router-link"
                :to="{ name: entry.route, params: { provider } }"
                class="app-more-menu__entry"
                @click="emit('navigate')"
            >
                <ui-icon :icon="entry.icon" fixed-width />
                {{ entry.label }}
            </ui-menu-item>
        </ui-menu>
        <ui-menu class="app-more-menu__about">
            <ui-menu-item
                v-for="link in about.links"
                :key="link.href"
                tag="a"
                :href="link.href"
                @click="emit('navigate')"
            >
                <span class="grow">{{ link.label }}</span>
                <ui-icon v-if="link.icon" :icon="link.icon" />
            </ui-menu-item>
        </ui-menu>
        <div class="app-more-menu__version mt-2">{{ about.version }} · © {{ about.year }} kadet.net</div>
    </nav>
</template>

<script setup lang="ts">
import type { NavigationEntry } from "@/navigation";
import { useAboutLinks } from "@/composables/useAboutLinks";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiMenu from "@/components/ui/UiMenu.vue";
import UiMenuItem from "@/components/ui/UiMenuItem.vue";

defineOptions({ name: "AppMoreMenu" });

defineProps<{
    entries: NavigationEntry[];
    provider: string;
}>();

const emit = defineEmits<{
    (event: "navigate"): void;
}>();

const about = useAboutLinks();
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.app-more-menu {
    &__entries {
        border-bottom: 1px solid rgba($black, 0.1);
    }

    &__version {
        color: $text-muted;
        font-size: $small-font-size;
    }
}
</style>
