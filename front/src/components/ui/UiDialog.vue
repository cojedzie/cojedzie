<template>
    <div v-if="currentBehaviour === 'modal'" ref="root" class="ui-backdrop" role="dialog" @click="handleBackdropClick">
        <div class="ui-modal" v-bind="$attrs">
            <slot name="header" v-bind="{ title, handleCloseClick }">
                <div class="ui-modal__top-bar">
                    <div class="ui-modal__header">
                        <div class="ui-modal__title" role="heading">
                            <slot name="title">
                                {{ title }}
                            </slot>
                        </div>
                    </div>
                    <button class="btn btn-action ui-modal__close" @click.prevent="handleCloseClick">
                        <ui-icon icon="close" />
                    </button>
                </div>
            </slot>
            <slot />
            <div v-if="hasFooter()" class="ui-modal__footer">
                <slot name="footer" />
            </div>
        </div>
    </div>
    <div
        v-else
        ref="root"
        v-bind="$attrs"
        :class="['ui-popup', arrow && 'ui-popup--arrow', $attrs.class]"
        :style="{ zIndex: zIndex }"
        role="dialog"
    >
        <div v-if="arrow" ref="arrowElement" class="ui-popup__arrow" />
        <div v-if="hasHeader()" class="ui-popup__header">
            <slot name="header">
                <div class="ui-popup__heading" role="heading">
                    <slot name="title">
                        {{ title }}
                    </slot>
                </div>
            </slot>
        </div>
        <slot />
        <div v-if="hasFooter()" class="ui-popup__footer">
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
    const target = ev.target as HTMLElement;

    if (target.classList.contains("ui-backdrop")) {
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
