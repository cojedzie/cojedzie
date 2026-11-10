<template>
    <div class="select" :class="{ open: isOpen }" @keydown="handleKeyboardNavigation">
        <div ref="controlElement" class="control" tabindex="0">
            <div class="option selected" :class="{ empty: !modelValue }" @click="open()">
                <slot v-if="modelValue" :option="modelValue as T">
                    {{ modelValue }}
                </slot>
                <slot v-else name="empty"> Brak wyboru </slot>
            </div>
            <button v-if="allowClearing" class="action" @click="clear">
                <ui-icon icon="clear" />
            </button>
            <button class="action" tabindex="-1" @click="toggle">
                <ui-icon icon="ui-select:open" />
            </button>
        </div>
        <slot v-if="isOpen" name="options" :options="options">
            <slot name="options-popup" v-bind="{ options, isOpen, close, open, toggle }">
                <div ref="optionsPopupElement" class="popup">
                    <slot name="options" v-bind="{ options }">
                        <ul class="options">
                            <li
                                v-for="(option, index) in options"
                                :key="index"
                                class="option"
                                :class="{ hovered: index === selectedIndex }"
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

<script setup lang="ts" generic="T">
import { ref, PropType } from "vue";
import { onClickOutside } from "@vueuse/core";
import { FloatingPlacement, useFloating } from "@/composables/useFloating";
import UiIcon from "@/components/ui/UiIcon.vue";

const props = defineProps({
    allowEmpty: Boolean,
    allowClearing: {
        type: Boolean,
        default: props => props.allowEmpty,
    },
    allowInput: Boolean,
    modelValue: {
        type: null as PropType<T>,
        required: false,
        default: null,
    },
    options: {
        type: Array as PropType<T[]>,
        required: true,
    },
    placement: {
        type: String as PropType<FloatingPlacement>,
        default: "bottom-start",
    },
});

defineSlots<{
    default?: (props: { option: T }) => unknown;
    empty?: () => unknown;
    options?: (props: { options: T[] }) => unknown;
    "options-popup"?: (props: {
        options: T[];
        isOpen: boolean;
        close: () => void;
        open: () => void;
        toggle: () => void;
    }) => unknown;
}>();

const optionsPopupElement = ref<HTMLElement>(null);
const controlElement = ref<HTMLElement>(null);

useFloating(controlElement, optionsPopupElement, { placement: props.placement });

onClickOutside(optionsPopupElement, () => {
    close();
});

const emit = defineEmits<{
    (type: "update:modelValue", value: T, index: number): void;
    (type: "hover", value: T, index: number): void;
    (type: "clear", value: T);
}>();

const isOpen = ref<boolean>(false);
const selectedIndex = ref<number | null>(null);

function clear() {
    emit("update:modelValue", null, -1);
    emit("clear", props.modelValue as T);

    selectedIndex.value = null;

    close();
}

function select(item: T, index: number) {
    emit("update:modelValue", item, index);

    selectedIndex.value = null;

    close();
}

function hover(item: T, index: number) {
    emit("hover", item, index);

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

<style scoped>
@reference "@styles/tailwind.css";

.select {
    & .control {
        display: flex;
        cursor: pointer;
    }

    & .option {
        padding: --spacing(2) --spacing(3);
        cursor: pointer;

        &.hovered {
            background: var(--color-hover);
        }

        &.selected {
            flex: 1 1 auto;
        }

        &.empty {
            display: grid;
            align-items: center;
            font-weight: bold;
        }
    }

    & .action {
        padding: --spacing(2) --spacing(3);
        font-size: var(--text-base);

        &:hover {
            background-color: var(--color-hover);
        }

        &:active {
            background-color: var(--color-pressed);
        }
    }

    & .popup {
        @apply ui-popup;
        inline-size: 100%;
        margin-block-end: --spacing(4);
        padding: 0;
    }

    & .options {
        margin-block-end: 0;
        padding-inline-start: 0;
        list-style: none;
    }
}
</style>
