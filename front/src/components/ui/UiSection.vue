<template>
    <section class="ui-section">
        <header class="ui-section__header">
            <h2 class="ui-section__title">
                <slot name="title" v-bind="{ icon, title }">
                    <ui-icon v-if="icon" :icon="icon" fixed-width class="mr-1" />
                    {{ title }}
                </slot>
            </h2>
            <div class="ui-section__actions">
                <slot name="actions" />
                <button
                    v-if="collapsible"
                    class="btn btn-action"
                    :aria-expanded="collapsed ? 'false' : 'true'"
                    @click="collapsed = !collapsed"
                >
                    <ui-tooltip>
                        {{ collapsed ? "rozwiń" : "zwiń" }}
                        <span v-if="title" class="sr-only">sekcję „{{ title }}”</span>
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

<style lang="scss">
@import "../../../styles/_variables.scss";

$ui-section-safe-margin: 0.5rem;

.ui-section {
    // the background reaches past the content, so the page background doesn't touch the text
    padding: $ui-section-safe-margin;
    margin: (-$ui-section-safe-margin) (-$ui-section-safe-margin) 1rem;

    background: rgba(white, 0.85);
}

.ui-section__header {
    @include ui-section-header;
}

.ui-section__title {
    flex-grow: 1;
}

.ui-section__actions {
    display: flex;
    align-items: center;
    margin-top: -0.5rem;
    margin-bottom: -0.5rem;
}
</style>
