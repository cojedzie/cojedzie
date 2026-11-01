<template>
    <button
        ref="chip"
        class="provider-switcher__chip flex items-center gap-1 rounded-full px-3 py-1 text-sm"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="dialog"
        @click="open = !open"
    >
        <ui-icon icon="provider" />
        <span class="provider-switcher__name">{{ current.name }}</span>
        <ui-icon icon="chevron-down" />
    </button>
    <teleport to="#popups">
        <component
            :is="desktop ? UiDialog : UiSheet"
            v-if="open"
            v-bind="desktop ? { reference: chip, arrow: true, placement: 'bottom-start' } : {}"
            title="Lokalizacja"
            class="min-w-64"
            @leave="open = false"
        >
            <ui-menu v-if="providers.length > 0" class="provider-switcher__providers">
                <ui-menu-item
                    v-for="provider in providers"
                    :key="provider.id"
                    class="provider-switcher__provider"
                    :class="[provider.id == current.id && 'provider-switcher__provider--active']"
                    :aria-current="provider.id === current.id ? 'true' : 'false'"
                    @click="pick(provider.id)"
                >
                    <ui-icon icon="provider" />
                    {{ provider.name }}
                    <ui-icon v-if="provider.id === current.id" icon="add" class="ms-auto" />
                </ui-menu-item>
            </ui-menu>
            <p v-else class="provider-switcher__empty m-0 text-muted">Brak dostępnych lokalizacji</p>
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

<style lang="scss">
@import "../../../styles/_variables.scss";

.provider-switcher {
    &__chip {
        @include app-bar-control;
        color: inherit;
    }

    &__provider--active {
        color: $primary;
    }
}
</style>
