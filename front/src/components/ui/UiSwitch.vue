<template>
    <div class="ui-switch" :class="[value && 'ui-switch--checked']" v-bind="$attrs" @click="handleSwitchClick">
        <div class="ui-switch__track">
            <div class="ui-switch__thumb" />
        </div>
        <input type="checkbox" class="ui-switch__checkbox" :checked="value" @change="handleCheckboxChange" />
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

<style lang="scss">
@import "../../../styles/_variables.scss";

$ui-switch-marker-size: 0.7rem !default;
$ui-switch-spacing: 1px !default;
$ui-switch-duration: 150ms !default;
$ui-switch-width-factor: 2.25 !default;

.ui-switch {
    padding: 3px;

    &__checkbox {
        display: none;
    }

    &__track {
        position: relative;
        box-sizing: content-box;
        width: $ui-switch-width-factor * $ui-switch-marker-size;
        height: $ui-switch-marker-size;
        padding: $ui-switch-spacing;
        border: 1px solid $dark;
        border-radius: $ui-switch-marker-size;
        background: white;
        transition: background-color $ui-switch-duration ease-in-out;
        cursor: pointer;
    }

    &__thumb {
        position: absolute;
        left: 0;
        width: $ui-switch-marker-size;
        height: $ui-switch-marker-size;
        margin-inline-start: $ui-switch-spacing;
        border-radius: 100%;
        background: $dark;
        transition:
            background-color $ui-switch-duration ease-in-out,
            left $ui-switch-duration ease-in-out;
    }

    &--checked &__track {
        background: $dark;
    }

    &--checked &__thumb {
        left: ($ui-switch-width-factor - 1) * $ui-switch-marker-size;
        background: white;
    }
}
</style>
