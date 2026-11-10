<template>
    <section class="section">
        <header class="header">
            <h2 class="title">
                <slot name="title" v-bind="{ icon, title }">
                    <ui-icon v-if="icon" :icon="icon" fixed-width class="title-icon" />
                    {{ title }}
                </slot>
            </h2>
            <div class="tools">
                <slot name="actions" />
                <button
                    v-if="collapsible"
                    class="btn btn-action"
                    :aria-expanded="collapsed ? 'false' : 'true'"
                    @click="collapsed = !collapsed"
                >
                    <ui-tooltip>
                        {{ collapsed ? "rozwiń" : "zwiń" }}
                        <span v-if="title" class="visually-hidden">sekcję „{{ title }}”</span>
                    </ui-tooltip>
                    <ui-icon :icon="collapsed ? 'chevron-down' : 'chevron-up'" fixed-width />
                </button>
            </div>
        </header>
        <ui-fold v-if="collapsible" :visible="!collapsed">
            <slot />
        </ui-fold>
        <slot v-else />
    </section>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { PredefinedIcon } from "@/icons";
import UiFold from "@/components/ui/UiFold.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

defineOptions({ name: "UiSection" });

defineProps({
    icon: { type: String as PropType<PredefinedIcon>, default: undefined },
    title: { type: String, default: undefined },
    collapsible: { type: Boolean },
});

// works without a binding too, then the section keeps the state itself
const collapsed = defineModel<boolean>("collapsed", { default: false });

defineSlots<{
    default?: () => unknown;
    title?: (props: { icon?: PredefinedIcon; title?: string }) => unknown;
    actions?: () => unknown;
}>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.section {
    /* the background reaches past the content, so the page background doesn't touch the text */
    padding: --spacing(2);
    margin: calc(--spacing(2) * -1) calc(--spacing(2) * -1) --spacing(4);
    background: var(--color-surface);

    & .header {
        @apply ui-section-header;
    }

    & .title {
        flex-grow: 1;
    }

    & .title-icon {
        margin-inline-end: --spacing(1);
    }

    & .tools {
        display: flex;
        align-items: center;
        margin-block: calc(--spacing(2) * -1);
    }
}
</style>
