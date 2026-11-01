<template>
    <div ref="marker" class="-mb-px h-px" aria-hidden="true" />
    <header class="app-header sticky top-0" :class="{ 'app-header--stuck': stuck }">
        <div class="page-container flex min-h-12 items-center gap-4">
            <router-link :to="home" class="app-header__logo">
                <img src="@resources/images/logo.png" alt="co jedzie logo" class="h-6" />
            </router-link>
            <provider-switcher v-if="provider" :current="current" :providers="providers" @select="switchProvider" />
            <nav v-if="provider" class="app-header__nav hidden items-center gap-2 text-sm md:flex">
                <router-link
                    v-for="entry in navigation"
                    :key="entry.id"
                    :to="{ name: entry.route, params: { provider } }"
                    class="app-header__link flex items-center self-stretch px-4 py-1"
                    exact-active-class="app-header__link--active"
                >
                    {{ entry.label }}
                </router-link>
                <button
                    ref="more"
                    class="app-header__link flex items-center self-stretch px-4 py-1"
                    :aria-expanded="moreOpen ? 'true' : 'false'"
                    aria-haspopup="dialog"
                    aria-label="Więcej"
                    @click="moreOpen = !moreOpen"
                >
                    <ui-icon icon="more" />
                </button>
            </nav>
        </div>
        <teleport to="#popups">
            <ui-dialog v-if="moreOpen" :reference="moreButton" arrow placement="bottom-end" @leave="moreOpen = false">
                <app-more-menu :entries="[]" :provider="provider" @navigate="moreOpen = false" />
            </ui-dialog>
        </teleport>
    </header>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAsyncState, useIntersectionObserver } from "@vueuse/core";
import { navigation } from "@/navigation";
import useService from "@/composables/useService";
import ProviderRepository from "@/services/ProviderRepository";
import { useSelectionStore } from "@/stores/selection";
import AppMoreMenu from "@/components/app/AppMoreMenu.vue";
import ProviderSwitcher from "@/components/app/ProviderSwitcher.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "AppHeader" });

const route = useRoute();
const router = useRouter();
const selection = useSelectionStore();
const repository = useService(ProviderRepository.service);

// a failed request leaves the list empty, the switcher says so
const { state: providers } = useAsyncState(() => repository.getAllProviders(), []);

const provider = computed(() => route.params.provider as string | undefined);
const home = computed(() =>
    provider.value ? { name: "departures", params: { provider: provider.value } } : { name: "providers" },
);
// the details load after the page mounts, until then the id from the url is all we have
const current = computed(() => ({
    id: provider.value,
    name: selection.provider?.id === provider.value ? selection.provider.name : provider.value,
}));

// the marker scrolls away with the page while the header sticks, so once it is out of view the header is stuck
// and gets its shadow
const marker = useTemplateRef<HTMLElement>("marker");
const stuck = ref(false);
useIntersectionObserver(marker, ([entry]) => (stuck.value = !entry.isIntersecting));

const moreButton = useTemplateRef<HTMLButtonElement>("more");
const moreOpen = ref(false);

function switchProvider(id: string) {
    if (id === provider.value) {
        return;
    }

    // the selected stops belong to the old provider's network
    selection.clear();
    router.push({ name: "departures", params: { provider: id } });
}
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.app-header {
    z-index: $app-bar-z-index;
    background: $white;
    transition: box-shadow 0.2s ease;

    &--stuck {
        box-shadow: 0 1px 3px $app-bar-shadow-color;
    }

    &__logo {
        &,
        &:hover {
            color: inherit;
            text-decoration: none;
        }
    }

    &__link {
        @include app-nav-item;
    }
}
</style>
