<template>
    <div class="input-group input-group-sm">
        <input
            type="text"
            class="form-control form-control-sm"
            inputmode="numeric"
            v-bind="$attrs"
            :value="value"
            @blur="update"
        />
        <div class="input-group-append">
            <button class="btn btn-addon" type="button" :disabled="!canIncrement" @click="increment">
                <ui-icon icon="increment" />
            </button>
            <button class="btn btn-addon" type="button" :disabled="!canDecrement" @click="decrement">
                <ui-icon icon="decrement" />
            </button>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { defineProps, defineEmits, computed } from "vue";

const props = defineProps({
    value: {
        type: Number,
        required: true,
    },
    step: {
        type: Number,
        default: 1,
    },
    min: {
        type: Number,
        default: -Infinity,
    },
    max: {
        type: Number,
        default: +Infinity,
    },
});

const emit = defineEmits<{
    (event: "update:value", value: number): void;
}>();

const clamp = (value: number) => Math.max(Math.min(value, props.max), props.min);

function update(ev: InputEvent) {
    const target = ev.target as HTMLInputElement;
    emit("update:value", clamp(Number.parseInt(target.value)));
}

const increment = () => emit("update:value", clamp(props.value + props.step));
const decrement = () => emit("update:value", clamp(props.value - props.step));

const canIncrement = computed(() => props.max - props.value > Number.EPSILON * 2);
const canDecrement = computed(() => props.value - props.min > Number.EPSILON * 2);
</script>
