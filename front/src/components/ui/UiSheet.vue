<template>
    <teleport to="#popups">
        <transition name="ui-sheet" appear>
            <div class="ui-sheet" @click.self="emit('leave', $event)">
                <div class="ui-sheet__panel" role="dialog" :aria-label="title">
                    <div v-if="title" class="ui-sheet__title" role="heading">{{ title }}</div>
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

<style lang="scss">
@import "../../../styles/_variables.scss";

.ui-sheet {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: rgba($black, 0.4);

    &__panel {
        max-height: 80vh;
        overflow-y: auto;
        overscroll-behavior-y: contain;
        padding: $spacer $spacer calc($spacer + env(safe-area-inset-bottom));
        background: $white;
        border-radius: 0.75rem 0.75rem 0 0;
        box-shadow: 0 -0.25rem 1rem rgba($black, 0.2);
    }

    &__title {
        margin-bottom: 0.5rem;
        font-weight: bold;
    }

    &-enter-active,
    &-leave-active {
        transition: background-color 0.2s ease;

        .ui-sheet__panel {
            transition: translate 0.2s ease;
        }
    }

    &-enter-from,
    &-leave-to {
        background-color: transparent;

        .ui-sheet__panel {
            translate: 0 100%;
        }
    }
}
</style>
