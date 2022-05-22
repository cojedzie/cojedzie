<template>
    <slot name="button" v-bind="{ toggle, ref: el => button = el, $attrs }">
        <button v-bind="$attrs" ref="button" type="button" @click="toggle">
            <ui-icon icon="info" fixed-width />
        </button>
    </slot>
    <teleport v-if="active" to="#popups">
        <ui-dialog
            :reference="button"
            offset="-12px, 20px"
            class="help"
            placement="right-start"
            mobile-behaviour="modal"
        >
            <template #title>
                <slot name="title" />
            </template>
            <slot />
        </ui-dialog>
    </teleport>
</template>

<script lang="ts">
import { defineComponent, PropType, ref } from "vue";
import { supply } from "@/utils";
import { createMutex, Mutex, useMutex } from "@/composables/useMutex";

export type HelpTrigger = "click" | "long-hover";

const mutex = createMutex();

export default defineComponent({
    props: {
        trigger: {
            type: Array as PropType<HelpTrigger[]>,
            required: false,
            default: supply<HelpTrigger[]>(["click", "long-hover"])
        },
        mutex: {
            type: Object as PropType<Mutex>,
            required: false,
            default: mutex,
        }
    },
    setup(props) {
        const button = ref<HTMLElement>(null);
        const { active, toggle } = useMutex(props.mutex);

        return {
            active,
            toggle,
            button,
        }
    },
})
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

$ui-help-width: 300px !default;
$ui-help-spacing: 0.5rem !default;
$ui-help-radius: 3px !default;

.help {
    box-sizing: content-box;
    width: $ui-help-width;
    font-size: 0.8rem;
}

.help__image {
    display: block;
    width: 100%;
    border-radius: $ui-help-radius;
    margin-bottom: $ui-help-spacing;
    margin-top: $ui-help-spacing / 2;
    aspect-ratio: 2 / 1;
    box-shadow: 0 0 1px black;
}

.help__figure {
    figcaption {
        color: $text-muted;
    }

    margin-bottom: $ui-help-spacing;
}

.help__image {
    margin-bottom: $ui-help-spacing / 4;
}
</style>
