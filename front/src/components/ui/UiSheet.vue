<template>
    <teleport to="#popups">
        <transition name="sheet" appear>
            <div class="sheet" @click.self="emit('leave', $event)">
                <div class="panel" role="dialog" :aria-label="title">
                    <div v-if="title" class="title" role="heading">{{ title }}</div>
                    <slot />
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted } from "vue";

defineOptions({ name: "UiSheet" });

defineProps<{ title?: string }>();

const emit = defineEmits<{
    (event: "leave", originalEvent?: Event): void;
}>();

defineSlots<{ default?: () => unknown }>();

function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") {
        emit("leave", event);
    }
}

onMounted(() => document.addEventListener("keydown", handleKeydown));
onBeforeUnmount(() => document.removeEventListener("keydown", handleKeydown));
</script>

<style scoped>
@reference "@styles/tailwind.css";

.sheet {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: var(--color-backdrop);

    & .panel {
        max-block-size: 80vh;
        overflow-y: auto;
        overscroll-behavior-y: contain;
        padding: --spacing(4) --spacing(4) calc(--spacing(4) + env(safe-area-inset-bottom));
        background: white;
        border-start-start-radius: var(--radius-xl);
        border-start-end-radius: var(--radius-xl);
        box-shadow: var(--shadow-sheet);
    }

    & .title {
        margin-block-end: --spacing(2);
        font-weight: bold;
    }

    &:is(.sheet-enter-active, .sheet-leave-active) {
        transition: background-color var(--duration-normal) var(--ease-in-out);

        & .panel {
            transition: translate var(--duration-normal) var(--ease-in-out);
        }
    }

    &:is(.sheet-enter-from, .sheet-leave-to) {
        background-color: transparent;

        & .panel {
            translate: 0 100%;
        }
    }
}
</style>
