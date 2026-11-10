<template>
    <button
        ref="chip"
        class="chip"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="dialog"
        @click="open = !open"
    >
        <ui-icon icon="provider" />
        <span>{{ current.name }}</span>
        <ui-icon icon="chevron-down" />
    </button>
    <teleport to="#popups">
        <component
            :is="desktop ? UiDialog : UiSheet"
            v-if="open"
            v-bind="desktop ? { reference: chip, arrow: true, placement: 'bottom-start' } : {}"
            title="Lokalizacja"
            class="popup"
            @leave="open = false"
        >
            <ui-menu v-if="providers.length > 0">
                <ui-menu-item
                    v-for="provider in providers"
                    :key="provider.id"
                    :aria-current="provider.id === current.id ? 'true' : 'false'"
                    @click="pick(provider.id)"
                >
                    <ui-icon icon="provider" />
                    {{ provider.name }}
                    <ui-icon v-if="provider.id === current.id" icon="add" class="current" />
                </ui-menu-item>
            </ui-menu>
            <p v-else class="empty">Brak dostępnych lokalizacji</p>
        </component>
    </teleport>
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from "vue";
import { useMediaQuery } from "@vueuse/core";
import type { Provider } from "@/model";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiMenu from "@/components/ui/UiMenu.vue";
import UiMenuItem from "@/components/ui/UiMenuItem.vue";
import UiSheet from "@/components/ui/UiSheet.vue";

defineOptions({ name: "ProviderSwitcher" });

defineProps<{
    current: { id: string; name: string };
    providers: Provider[];
}>();

const emit = defineEmits<{
    (event: "select", id: string): void;
}>();

const chip = useTemplateRef<HTMLButtonElement>("chip");
const open = ref(false);
const desktop = useMediaQuery("(min-width: 768px)");

function pick(id: string) {
    open.value = false;
    emit("select", id);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.chip {
    @apply app-bar-control;
    display: flex;
    align-items: center;
    gap: --spacing(1);
    padding: --spacing(1) --spacing(3);
    border-radius: var(--radius-full);
    color: inherit;
    font-size: var(--text-sm);
}

.popup {
    min-inline-size: --spacing(64);
}

.current {
    margin-inline-start: auto;
}

.empty {
    margin: 0;
    color: var(--color-muted);
}
</style>
