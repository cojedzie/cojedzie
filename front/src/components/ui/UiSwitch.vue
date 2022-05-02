<template>
    <div class="ui-switch" :class="[ value && 'ui-switch--checked' ]" v-bind="$attrs" @click="handleSwitchClick">
        <div class="ui-switch__track"><div class="ui-switch__thumb"></div></div>
        <input type="checkbox" class="ui-switch__checkbox" :id="id" :checked="value" @change="handleCheckboxChange"/>
    </div>
</template>

<script lang="ts">
export default {
    name: "UiSwitch",
    inheritAttrs: false
}
</script>

<script lang="ts" setup>
import { defineEmits, defineProps } from "vue";

const props = defineProps({
    value: {
        type: Boolean,
        required: true,
    }
});

const emit = defineEmits<{
    (event: 'update:value', value: boolean): void;
}>()

const handleSwitchClick = () => emit('update:value', !props.value);
const handleCheckboxChange = (ev: Event) => emit('update:value', (ev.target as HTMLInputElement).checked);
</script>
