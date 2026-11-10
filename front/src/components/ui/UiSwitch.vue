<template>
    <div class="switch" :class="[value && 'checked']" v-bind="$attrs" @click="handleSwitchClick">
        <div class="track">
            <div class="thumb" />
        </div>
        <input type="checkbox" class="checkbox" :checked="value" @change="handleCheckboxChange" />
    </div>
</template>

<script lang="ts" setup>
defineOptions({
    name: "UiSwitch",
    inheritAttrs: false,
});

const props = defineProps({
    value: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits<{
    (event: "update:value", value: boolean): void;
}>();

const handleSwitchClick = () => emit("update:value", !props.value);
const handleCheckboxChange = (ev: Event) => emit("update:value", (ev.target as HTMLInputElement).checked);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.switch {
    /* the switch's own geometry: the thumb's size and how far the track reaches past it */
    --switch-thumb: 0.7rem;
    --switch-length: 2.25;

    padding: --spacing(1);

    & .checkbox {
        display: none;
    }

    & .track {
        position: relative;
        box-sizing: content-box;
        inline-size: calc(var(--switch-length) * var(--switch-thumb));
        block-size: var(--switch-thumb);
        /* hairlines: the gap around the thumb and the outline */
        padding: 1px;
        border: 1px solid var(--color-dark);
        border-radius: var(--radius-full);
        background: white;
        transition: background-color var(--duration-fast) var(--ease-in-out);
        cursor: pointer;
    }

    & .thumb {
        position: absolute;
        inset-inline-start: 0;
        inline-size: var(--switch-thumb);
        block-size: var(--switch-thumb);
        margin-inline-start: 1px;
        border-radius: var(--radius-full);
        background: var(--color-dark);
        transition-property: background-color, inset-inline-start;
        transition-duration: var(--duration-fast);
        transition-timing-function: var(--ease-in-out);
    }

    &.checked {
        & .track {
            background: var(--color-dark);
        }

        & .thumb {
            inset-inline-start: calc((var(--switch-length) - 1) * var(--switch-thumb));
            background: white;
        }
    }
}
</style>
