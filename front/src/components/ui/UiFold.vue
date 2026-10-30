<template>
    <div
        ref="root"
        class="fold"
        :class="[`fold--${state}`]"
        :aria-expanded="visible ? 'true' : 'false'"
        @transitionend="handleTransitionEnd"
    >
        <div ref="inner" class="fold__inner" :tabindex="visible ? null : -1">
            <Lazy v-if="lazy" :activate="visible">
                <slot />
            </Lazy>
            <slot v-else />
        </div>
    </div>
</template>

<script lang="ts">
export type UiFoldState = "folded" | "expanding" | "expanded" | "folding";
</script>

<script lang="ts" setup>
import { ref, watch } from "vue";
import Lazy from "@/components/utils/Lazy.vue";

defineOptions({ name: "UiFold" });

const props = defineProps({
    visible: { type: Boolean },
    lazy: { type: Boolean },
});

const root = ref<HTMLDivElement>();
const inner = ref<HTMLDivElement>();
const state = ref<UiFoldState>(props.visible ? "expanded" : "folded");

watch(
    () => props.visible,
    visible => (visible ? expand() : fold()),
);

function handleTransitionEnd(event: TransitionEvent) {
    if (event.target !== root.value) {
        // it's not about us
        return;
    }

    switch (state.value) {
        case "expanded":
        case "expanding":
            root.value.style.height = "auto";
            state.value = "expanded";
            break;
        case "folded":
        case "folding":
            state.value = "folded";
    }
}

function fold() {
    switch (state.value) {
        case "folded":
        case "folding":
            // no-op, it's already folded
            break;

        case "expanded":
            root.value.style.height = `${root.value.clientHeight}px`;

            // DOM must be updated and style must be applied in order for this to work
            setTimeout(() => {
                root.value.style.height = "0px";
                state.value = "folding";
            });
            break;

        case "expanding":
            // reverse process
            root.value.style.height = "0px";
            state.value = "folding";
            break;
    }
}

function expand() {
    switch (state.value) {
        case "expanded":
        case "expanding":
            // no-op, it's already expanded
            break;

        case "folded":
        case "folding":
            root.value.style.height = inner.value.clientHeight + "px";
            state.value = "expanding";
            break;
    }
}
</script>
