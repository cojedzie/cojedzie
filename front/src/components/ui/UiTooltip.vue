<template>
    <teleport to="#popups" :disabled="noTeleport">
        <transition name="tooltip">
            <ui-dialog
                v-if="isVisible"
                class="ui-popup--tooltip"
                aria-hidden="true"
                arrow
                :reference="anchor"
                :placement="placement"
                :responsive="false"
            >
                <slot />
            </ui-dialog>
        </transition>
    </teleport>
    <span ref="root" class="sr-only"><slot /></span>
</template>

<script lang="ts">
import { Ref } from "vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import { FloatingPlacement } from "@/composables/useFloating";

export type UiTooltipPlacement = FloatingPlacement;
export type UiTooltipTrigger = "hover" | "focus" | "long-press";

export const openedTooltips = new Set<{ show: Ref<boolean> }>();

document.addEventListener("touchstart", () => {
    for (const tooltip of openedTooltips.values()) {
        tooltip.show.value = false;
    }
});
</script>

<script lang="ts" setup>
import { computed, onBeforeUnmount, PropType, ref, shallowRef, watch } from "vue";
import { useBrowserContext } from "@/composables/useBrowserContext";

defineOptions({ name: "UiTooltip" });

const longPressTimeout = 1000;

const props = defineProps({
    placement: {
        type: String as PropType<UiTooltipPlacement>,
        default: "top",
    },
    triggers: {
        type: Array as PropType<UiTooltipTrigger[]>,
        default: () => ["hover", "focus", "long-press"],
    },
    delay: {
        type: Number,
        default: 400,
    },
    noTeleport: {
        type: Boolean,
        default: false,
    },
    permanent: {
        type: Boolean,
        default: false,
    },
});

const root = shallowRef<HTMLElement>(null);
const show = ref<boolean>(false);
const anchor = computed(() => root.value?.parentElement);
const isVisible = computed(() => show.value || props.permanent);

const { isTouch } = useBrowserContext();

let events: Record<string, (event: Event) => void> = {};

const exposed = { show };

defineExpose(exposed);

watch(isVisible, isVisible => {
    if (isVisible) {
        openedTooltips.add(exposed);
    } else {
        openedTooltips.delete(exposed);
    }
});

function registerEventListeners() {
    for (const [event, handler] of Object.entries(events)) {
        anchor.value.addEventListener(event, handler);
    }
}

function removeEventListeners() {
    for (const [event, handler] of Object.entries(events)) {
        anchor.value.removeEventListener(event, handler);
    }
}

function updateTriggers() {
    removeEventListeners();

    events = {};

    let blocked = false;

    if (props.triggers.includes("hover") && !isTouch) {
        let timeout;

        events["mouseenter"] = () => {
            timeout = setTimeout(() => {
                show.value = !blocked;
            }, props.delay);
        };

        events["mouseleave"] = () => {
            clearTimeout(timeout);
            show.value = false;
        };
    }

    if (props.triggers.includes("focus") || (props.triggers.includes("hover") && isTouch)) {
        if (isTouch) {
            events["touchstart"] = () => {
                // this is to prevent showing tooltips after tap
                blocked = true;
                setTimeout(() => (blocked = false), longPressTimeout - 50);
            };
        }

        events["focus"] = () => {
            show.value = !blocked;
        };

        events["blur"] = () => {
            show.value = false;
        };
    }

    if (props.triggers.includes("long-press") && isTouch) {
        let timeout;

        events["touchstart"] = () => {
            timeout = setTimeout(() => {
                show.value = true;
            }, longPressTimeout);

            // this is to prevent showing tooltips after tap
            blocked = true;
            setTimeout(() => (blocked = false), longPressTimeout - 50);
        };

        events["touchend"] = ev => {
            clearTimeout(timeout);

            if (show.value) {
                ev.preventDefault();
                anchor.value.focus();
            }
        };

        events["blur"] = () => {
            show.value = false;
        };
    }

    registerEventListeners();
}

watch([() => props.triggers, anchor], updateTriggers, { deep: true });

onBeforeUnmount(removeEventListeners);
</script>
