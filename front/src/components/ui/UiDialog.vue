<template>
    <div v-if="currentBehaviour === 'modal'" ref="root" class="backdrop" role="dialog" @click="handleBackdropClick">
        <div class="modal" :class="[size, flush && 'flush']" v-bind="$attrs">
            <slot name="header" v-bind="{ title, handleCloseClick }">
                <div class="top-bar">
                    <div class="heading" role="heading">
                        <slot name="title">
                            {{ title }}
                        </slot>
                    </div>
                    <button class="btn btn-action close" @click.prevent="handleCloseClick">
                        <ui-icon icon="close" />
                    </button>
                </div>
            </slot>
            <slot />
            <div v-if="hasFooter()" class="footer">
                <slot name="footer" />
            </div>
        </div>
    </div>
    <div
        v-else
        ref="root"
        v-bind="$attrs"
        :class="['popup', variant, arrow && 'with-arrow', flush && 'flush', $attrs.class]"
        :style="{ zIndex: zIndex }"
        role="dialog"
    >
        <div v-if="arrow" ref="arrowElement" class="arrow" />
        <div v-if="hasHeader()" class="header">
            <slot name="header">
                <div class="heading" role="heading">
                    <slot name="title">
                        {{ title }}
                    </slot>
                </div>
            </slot>
        </div>
        <slot />
        <div v-if="hasFooter()" class="footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<script lang="ts">
import UiIcon from "@/components/ui/UiIcon.vue";
/**
 * How popup will be presented to user:
 *  - "modal" - modal window
 *  - "popup" - simple popup
 */
export type DialogBehaviour = "modal" | "popup";

// shared between all the dialogs, as only the first modal should lock the page
let openModalCounter = 0;
</script>

<script lang="ts" setup>
import {
    computed,
    ComponentInternalInstance,
    getCurrentInstance,
    nextTick,
    onActivated,
    onBeforeUnmount,
    onDeactivated,
    onMounted,
    PropType,
    ref,
    useSlots,
    watch,
} from "vue";
import { defaultBreakpoints } from "@/filters";
import { useRemoved } from "@/composables/useRemoved";
import { FloatingOptions, FloatingPlacement, positionFloating } from "@/composables/useFloating";

defineOptions({
    name: "UiDialog",
    inheritAttrs: false,
});

const props = defineProps({
    behaviour: { type: String as PropType<DialogBehaviour>, default: "popup" },
    mobileBehaviour: { type: String as PropType<DialogBehaviour> },
    reference: { type: [String, HTMLElement] as PropType<string | HTMLElement> },
    refs: { type: Object as PropType<Record<string, HTMLElement>> },
    placement: { type: String as PropType<FloatingPlacement>, default: "auto" },
    arrow: { type: Boolean },
    responsive: { type: Boolean, default: true },
    offset: { type: Object as PropType<FloatingOptions["offset"]>, default: null },
    title: { type: String },
    /** A popup drawn as a tooltip: dark, compact */
    variant: { type: String as PropType<"popup" | "tooltip">, default: "popup" },
    /** Without padding, for content that reaches the edges itself */
    flush: { type: Boolean },
    /** Width of a modal */
    size: { type: String as PropType<"small" | "medium" | "big" | "large" | "huge">, default: undefined },
});

const emit = defineEmits<{
    (event: "leave", originalEvent?: Event): void;
    (event: "close"): void;
}>();

defineSlots<{
    default?: () => unknown;
    header?: (props: { title?: string; handleCloseClick?: () => void }) => unknown;
    title?: () => unknown;
    footer?: () => unknown;
}>();

const slots = useSlots();
const instance = getCurrentInstance();

const root = ref<HTMLElement>();
const arrowElement = ref<HTMLElement>();
const isMobile = ref(false);
const zIndex = ref(1000);

let focusOutEvent: ((event: MouseEvent) => void) | undefined;
let stopPositioning: (() => void) | null = null;

function computeZIndexOfElement(element: HTMLElement): number {
    let current = element;

    while (current instanceof Element) {
        const zIndex = window.getComputedStyle(current).zIndex;

        if (zIndex !== "auto") {
            return parseInt(zIndex);
        }

        if (!current.parentElement) {
            break;
        }

        current = current.parentElement;
    }

    return 0;
}

function getRootElementOfComponent(component: { $el: HTMLElement } | HTMLElement): HTMLElement {
    return component instanceof HTMLElement ? component : component.$el;
}

function findClosestRef(component: ComponentInternalInstance, ref: string): HTMLElement | null {
    for (let current = component; current; current = current.parent) {
        if (Object.prototype.hasOwnProperty.call(current.refs, ref)) {
            return getRootElementOfComponent(current.refs[ref] as { $el: HTMLElement } | HTMLElement);
        }
    }

    return null;
}

function findClosestNonWrapperParent(component: ComponentInternalInstance): ComponentInternalInstance | null {
    let parent = component.parent;

    while (parent && (parent.proxy.$el === component.proxy.$el || !(parent.proxy.$el instanceof HTMLElement))) {
        parent = parent.parent;
    }

    return parent;
}

const currentBehaviour = computed<DialogBehaviour>(() => {
    if (!props.mobileBehaviour) {
        return props.behaviour;
    }

    return isMobile.value ? props.mobileBehaviour : props.behaviour;
});

const hasFooter = () => !!slots.footer;
const hasHeader = () => !!(slots.header || slots.title || props.title);

function getReferenceElement(): HTMLElement | null {
    if (typeof props.reference === "string") {
        if (props.reference[0] === "#") {
            return document.getElementById(props.reference.substring(1));
        }

        if (props.refs) {
            return props.refs[props.reference];
        }

        return findClosestRef(instance, props.reference);
    }

    if (props.reference instanceof HTMLElement) {
        return props.reference;
    }

    const parent = findClosestNonWrapperParent(instance);

    return parent && parent.proxy.$el;
}

function focusOut(event: MouseEvent) {
    if (root.value?.contains(event.target as Node)) {
        return;
    }

    emit("leave", event);
}

function mountModal() {
    if (openModalCounter === 0) {
        document.body.style.paddingRight = `${window.screen.width - document.body.clientWidth}px`;
        document.body.classList.add("contains-modal");
    }

    openModalCounter++;
}

function dismountModal() {
    openModalCounter--;

    if (openModalCounter === 0) {
        document.body.style.paddingRight = "";
        document.body.classList.remove("contains-modal");
    }
}

function mountPopup() {
    // the arrow sticks out of the popup, so keep the popup that far from the reference
    const arrowSize = props.arrow
        ? parseFloat(getComputedStyle(root.value).getPropertyValue("--ui-popup-arrow-size")) || 0
        : 0;

    stopPositioning = positionFloating(getReferenceElement(), root.value, {
        placement: props.placement,
        arrow: props.arrow ? arrowElement.value : undefined,
        offset: { mainAxis: (props.offset?.mainAxis ?? 0) + arrowSize, crossAxis: props.offset?.crossAxis ?? 0 },
        responsive: props.responsive,
    });

    nextTick(() => {
        document.addEventListener("click", (focusOutEvent = focusOut), { capture: true });
    });
}

function removePopup() {
    stopPositioning?.();
    stopPositioning = null;
}

function activate() {
    if (props.behaviour === "modal") {
        mountModal();
    }
}

function deactivate() {
    if (props.behaviour === "modal") {
        dismountModal();
    }
}

function handleBackdropClick(ev: Event) {
    // the backdrop itself, not the modal on it
    if (ev.target === ev.currentTarget) {
        emit("leave");
    }
}

function handleCloseClick() {
    emit("leave");
    emit("close");
}

function handleWindowResize() {
    isMobile.value = screen.width < defaultBreakpoints.md;
}

onMounted(() => {
    zIndex.value = computeZIndexOfElement(getReferenceElement()) + 100;

    handleWindowResize();

    if (props.behaviour === "popup") {
        mountPopup();
    }

    window.addEventListener("resize", handleWindowResize);

    activate();
});

onActivated(activate);
onDeactivated(deactivate);

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleWindowResize);

    if (focusOutEvent) {
        document.removeEventListener("click", focusOutEvent, { capture: true });
    }

    deactivate();
});

useRemoved(root, removePopup);

watch(currentBehaviour, (newBehaviour, oldBehaviour) => {
    if (oldBehaviour === "popup") {
        removePopup();
    }

    if (newBehaviour === "popup") {
        nextTick(mountPopup);
    }

    if (newBehaviour === "modal") {
        mountModal();
    }

    if (oldBehaviour === "modal") {
        dismountModal();
    }
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.backdrop {
    position: fixed;
    inset: 0;
    z-index: var(--z-index-modal);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: --spacing(4);
    overflow-y: auto;
    overscroll-behavior-y: contain;
    background: var(--color-backdrop);

    /* room above and below a modal taller than the screen */
    &::before,
    &::after {
        display: block;
        flex: 0 0 auto;
        inline-size: 1px;
        block-size: --spacing(4);
        content: "";
    }

    @variant max-md {
        padding: --spacing(1);
    }
}

.modal {
    position: relative;
    box-sizing: content-box;
    max-inline-size: 80%;
    margin: auto;
    padding: --spacing(8);
    border-radius: var(--radius-sm);
    background: white;
    box-shadow: var(--shadow-popup);

    /* the modal's widths */
    &.small {
        inline-size: 320px;
    }

    &.medium {
        inline-size: 480px;
    }

    &.big {
        inline-size: 640px;
    }

    &.large {
        inline-size: 860px;
    }

    &.huge {
        inline-size: 1024px;
    }

    @variant max-md {
        max-inline-size: calc(100% - --spacing(2));
        padding: --spacing(4);

        &:is(.small, .medium, .big, .large, .huge) {
            box-sizing: border-box;
            inline-size: 100%;
        }
    }

    &.flush {
        padding: 0;
    }

    & .top-bar {
        display: flex;
        margin-block-end: --spacing(6);
    }

    & .heading {
        flex: 1 1 auto;
        font-size: var(--text-sm);
        font-weight: bold;
    }

    & .close {
        /* in the corner, its padding filling the modal's */
        margin-block-start: calc(--spacing(8) * -1);
        margin-inline-end: calc(--spacing(8) * -1);
        padding: --spacing(8) --spacing(8) 0;
    }
}

.popup {
    @apply ui-popup;

    &.flush {
        padding: 0;
    }

    & .header {
        margin-block-end: --spacing(2);
    }

    & .heading {
        margin-block-end: --spacing(2);
        font-size: var(--text-sm);
        font-weight: bold;

        &:last-child {
            margin-block-end: 0;
        }
    }

    /* spans the page width on narrow screens, see positionFloating() */
    &[data-narrow] {
        margin-inline: --spacing(4);
    }

    &.tooltip {
        min-inline-size: 0;
        padding: --spacing(2) --spacing(3);
        background: var(--color-dark);
        color: white;
        font-size: var(--text-xs);
        font-weight: bold;
        box-shadow: none;
    }
}

/*
 * The arrow: floating-ui places the element on the popup's edge facing the reference (data-placement), its pseudo
 * elements draw a bordered triangle out of it. Placements are physical, so are the sides here.
 */
.with-arrow {
    /* UiDialog keeps the popup this far from its reference, to make room for the arrow */
    --ui-popup-arrow-size: 8px;
    --arrow-fill: white;
    --arrow-border: rgb(0 0 0 / 0.2);

    &.tooltip {
        --ui-popup-arrow-size: 6px;
        --arrow-fill: var(--color-dark);
        --arrow-border: transparent;
    }

    & .arrow {
        position: absolute;
        inline-size: 0;
        block-size: 0;

        &::before,
        &::after {
            position: absolute;
            inline-size: 0;
            block-size: 0;
            border-style: solid;
            border-color: transparent;
            content: "";
        }

        &::before {
            --size: calc(var(--ui-popup-arrow-size) + 1px);
            --colour: var(--arrow-border);
        }

        &::after {
            --size: var(--ui-popup-arrow-size);
            --colour: var(--arrow-fill);
        }
    }

    &[data-placement^="top"] .arrow {
        bottom: 0;

        &::before,
        &::after {
            bottom: calc(0.5px - var(--size));
            left: 50%;
            translate: -50% 0;
            border-width: var(--size) var(--size) 0;
            border-top-color: var(--colour);
        }
    }

    &[data-placement^="bottom"] .arrow {
        top: 0;

        &::before,
        &::after {
            top: calc(0.5px - var(--size));
            left: 50%;
            translate: -50% 0;
            border-width: 0 var(--size) var(--size);
            border-bottom-color: var(--colour);
        }
    }

    &[data-placement^="left"] .arrow {
        right: 0;

        &::before,
        &::after {
            right: calc(0.5px - var(--size));
            top: 50%;
            translate: 0 -50%;
            border-width: var(--size) 0 var(--size) var(--size);
            border-left-color: var(--colour);
        }
    }

    &[data-placement^="right"] .arrow {
        left: 0;

        &::before,
        &::after {
            left: calc(0.5px - var(--size));
            top: 50%;
            translate: 0 -50%;
            border-width: var(--size) var(--size) var(--size) 0;
            border-right-color: var(--colour);
        }
    }
}
</style>
