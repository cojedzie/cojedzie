<template>
    <div ref="marker" class="marker" aria-hidden="true" />
    <header class="header" :class="{ stuck }">
        <div class="page-container bar">
            <router-link :to="home" class="logo">
                <img src="@resources/images/logo.png" alt="co jedzie logo" />
            </router-link>
            <provider-switcher v-if="provider" :current="current" :providers="providers" @select="switchProvider" />
            <nav v-if="provider" class="nav">
                <router-link
                    v-for="entry in navigation"
                    :key="entry.id"
                    :to="{ name: entry.route, params: { provider } }"
                    class="link"
                    active-class="active"
                >
                    {{ entry.label }}
                </router-link>
                <button
                    ref="more"
                    class="link"
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

// the board clears what belonged to the old provider once it loads the new one, see selection.loadProvider
function switchProvider(id: string) {
    router.push({ name: "departures", params: { provider: id } });
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* scrolls away with the page while the header sticks, see `stuck` */
.marker {
    /* a hairline that takes no room */
    block-size: 1px;
    margin-block-end: -1px;
}

.header {
    position: sticky;
    inset-block-start: 0;
    z-index: var(--z-index-bar);
    background: white;
    transition: box-shadow var(--duration-normal) var(--ease-in-out);

    &.stuck {
        box-shadow: var(--shadow-bar);
    }

    & .bar {
        display: flex;
        align-items: center;
        gap: --spacing(4);
        min-block-size: --spacing(12);
    }

    & .logo {
        &,
        &:hover {
            color: inherit;
            text-decoration: none;
        }

        & img {
            block-size: --spacing(6);
        }
    }

    & .nav {
        display: none;
        align-items: center;
        gap: --spacing(2);
        font-size: var(--text-sm);

        @variant md {
            display: flex;
        }
    }

    & .link {
        @apply app-nav-item;
        display: flex;
        align-items: center;
        align-self: stretch;
        padding: --spacing(1) --spacing(4);

        &.active {
            @apply app-nav-item-active;
        }
    }
}
</style>
