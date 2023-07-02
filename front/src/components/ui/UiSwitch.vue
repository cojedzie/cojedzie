<template>
    <div class="ui-switch" :class="[value && 'ui-switch--checked']" v-bind="$attrs" @click="handleSwitchClick">
        <div class="ui-switch__track">
            <div class="ui-switch__thumb" />
        </div>
        <input :id="id" type="checkbox" class="ui-switch__checkbox" :checked="value" @change="handleCheckboxChange" />
    </div>
</template>

<script lang="ts">
export default {
    name: "UiSwitch",
    inheritAttrs: false,
};
</script>

<script lang="ts" setup>
import { defineEmits, defineProps } from "vue";

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
}

.ui-switch__checkbox {
    display: none;
}

.ui-switch__track {
    border: 1px solid $dark;
    border-radius: $ui-switch-marker-size;
    padding: $ui-switch-spacing;
    width: $ui-switch-width-factor * $ui-switch-marker-size;
    height: $ui-switch-marker-size;
    position: relative;
    box-sizing: content-box;
    background: white;
    transition: background-color $ui-switch-duration ease-in-out;
    cursor: pointer;
}

.ui-switch__thumb {
    border-radius: 100%;
    width: $ui-switch-marker-size;
    height: $ui-switch-marker-size;
    background: $dark;
    position: absolute;
    transition: all $ui-switch-duration ease-in-out;
    transition-property: background-color, left;
    margin-left: $ui-switch-spacing;
    left: 0;
}

.ui-switch--checked {
    .ui-switch__thumb {
        background: white;
        left: ($ui-switch-width-factor - 1) * $ui-switch-marker-size;
    }

    .ui-switch__track {
        background: $dark;
    }
}
</style>
