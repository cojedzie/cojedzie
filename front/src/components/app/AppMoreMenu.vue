<template>
    <nav class="more-menu">
        <ui-menu v-if="entries.length > 0" class="entries">
            <ui-menu-item
                v-for="entry in entries"
                :key="entry.id"
                tag="router-link"
                :to="{ name: entry.route, params: { provider } }"
                @click="emit('navigate')"
            >
                <ui-icon :icon="entry.icon" fixed-width />
                {{ entry.label }}
            </ui-menu-item>
        </ui-menu>
        <ui-menu class="about">
            <ui-menu-item
                v-for="link in about.links"
                :key="link.href"
                tag="a"
                :href="link.href"
                @click="emit('navigate')"
            >
                <span class="link-name">{{ link.label }}</span>
                <ui-icon v-if="link.icon" :icon="link.icon" />
            </ui-menu-item>
        </ui-menu>
        <div class="version">{{ about.version }} · © {{ about.year }} kadet.net</div>
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

<style scoped>
@reference "@styles/tailwind.css";

.more-menu {
    min-inline-size: --spacing(48);

    & .entries {
        margin-block-end: --spacing(2);
        padding-block-end: --spacing(2);
        /* hairline */
        border-block-end: 1px solid rgb(0 0 0 / 0.1);
    }

    & .link-name {
        flex-grow: 1;
    }

    & .version {
        margin-block-start: --spacing(2);
        color: var(--color-muted);
        font-size: var(--text-xs);
    }
}
</style>
