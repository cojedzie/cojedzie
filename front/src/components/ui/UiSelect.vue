<template>
    <div class="ui-select" :class="{'ui-select--active': isOpen}" @keydown="handleKeyboardNavigation">
        <div class="ui-select__control" tabindex="0" ref="controlElement">
            <div class="ui-select__option ui-select__option--selected" :class="{ 'ui-select__option--empty': !modelValue }" @click="open()">
                <slot v-if="modelValue" :option="modelValue">
                    {{ modelValue }}
                </slot>
                <slot v-else name="empty">
                    Brak wyboru
                </slot>
            </div>
            <button v-if="allowClearing" class="ui-select__clear" @click="clear">
                <ui-icon icon="clear" />
            </button>
            <button class="ui-select__toggle" tabindex="-1" @click="toggle">
                <ui-icon icon="ui-select:open" />
            </button>
        </div>
        <slot v-if="isOpen" name="options" :options="options">
            <slot name="options-popup" v-bind="{ options, isOpen, close, open, toggle }">
                <div ref="optionsPopupElement" class="ui-select__options-popup">
                    <slot name="options" v-bind="{ options }">
                        <ul class="ui-select__options">
                            <li
                                v-for="(option, index) in options"
                                :key="option"
                                class="ui-select__option"
                                :class="{ 'ui-select__option--hovered': index === selectedIndex }"
                                @click="select(option, index)"
                                @mouseenter="hover(option, index)"
                            >
                                <slot :option="option">
                                    {{ option }}
                                </slot>
                            </li>
                        </ul>
                    </slot>
                </div>
            </slot>
        </slot>
    </div>
</template>

<script lang="ts" setup>
import { PropType, ref, reactive } from "vue";
import { onClickOutside } from "@vueuse/core"
import {usePopper} from "@/composables/usePopper";
import {Placement} from "popper.js";

const props = defineProps({
    allowEmpty: Boolean,
    allowClearing: {
        type: Boolean,
        default: props => props.allowEmpty,
    },
    allowInput: Boolean,
    modelValue: {
        type: null as PropType<unknown>,
        required: false,
        default: null,
    },
    options: {
        type: Array as PropType<unknown[]>,
        required: true,
    },
    placement: {
        type: String as PropType<Placement>,
        default: "bottom-start",
    }
})

const optionsPopupElement = ref<HTMLElement>(null);
const controlElement = ref<HTMLElement>(null);

usePopper(controlElement, optionsPopupElement, reactive({
    placement: props.placement
}))

onClickOutside(optionsPopupElement, () => {
    close();
})

const emit = defineEmits<{
    (type: "update:modelValue", value: unknown, index: number): void,
    (type: "hover", value: unknown, index: number): void,
    (type: "clear", value: unknown),
}>();

const isOpen = ref<boolean>(false);
const selectedIndex = ref<number | null>(null);

function clear() {
    emit('update:modelValue', null, -1);
    emit('clear', props.modelValue);

    selectedIndex.value = null;

    close();
}

function select(item: unknown, index: number) {
    emit('update:modelValue', item, index);

    selectedIndex.value = null;

    close();
}

function hover(item: unknown, index: number) {
    emit('hover', item, index);

    selectedIndex.value = index;
}

function toggle() {
    isOpen.value = !isOpen.value;
}

function open() {
    isOpen.value = true;

}

function close() {
    isOpen.value = false;
}

function handleKeyboardNavigation(e: KeyboardEvent) {
    switch (e.key) {
        case "Escape":
            close();
            break;
        case "Enter":
            if (!isOpen.value) {
                open();
            } else {
                select(props.options[selectedIndex.value], selectedIndex.value);
            }
            break;
        case "ArrowDown":
            selectedIndex.value++;
            break;
        case "ArrowUp":
            selectedIndex.value--;
            break;
        default:
            // prevent prevent default
            return;
    }

    e.preventDefault();
}

</script>
