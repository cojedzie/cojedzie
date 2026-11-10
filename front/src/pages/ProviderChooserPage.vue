<template>
    <div class="chooser">
        <ui-map :center="{ lat: 52.0194, lon: 19.1451 }" :zoom="7" :options="{ zoomControl: false }" class="map">
            <div class="picker">
                <h2 class="heading">Wybierz lokalizację</h2>
                <ul class="providers">
                    <li v-for="provider in providers" :key="provider.id">
                        <a :href="`/${provider.id}`" class="provider">
                            <ui-icon icon="line-bus" class="provider-icon" />
                            <div>
                                <div class="short-name">{{ provider.shortName }}</div>
                                <div class="name">{{ provider.name }}</div>
                            </div>
                            <ui-tooltip v-if="provider.lastUpdate != null"
                                >Ostatnia aktualizacja: {{ formatDateTime(provider.lastUpdate, timeZone) }}</ui-tooltip
                            >
                        </a>
                    </li>
                </ul>
            </div>

            <l-marker
                v-for="provider in providers"
                :key="provider.id"
                :lat-lng="provider.location"
                :options="{ keyboard: false }"
            >
                <l-icon>
                    <div class="label-box" tabindex="0">
                        <a :href="`/${provider.id}`" class="provider">
                            <ui-icon icon="line-bus" class="marker-icon" />
                            <div>
                                <div class="short-name">{{ provider.shortName }}</div>
                                <div class="name">{{ provider.name }}</div>
                            </div>
                        </a>
                    </div>
                </l-icon>
            </l-marker>
        </ui-map>
    </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted } from "vue";
import { useAsyncState } from "@vueuse/core";
import useService from "@/composables/useService";
import ProviderRepository from "@/services/ProviderRepository";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import { useDisplayTimeZone } from "@/composables/useDisplayTimeZone";
import { formatDateTime } from "@/time";

const UiMap = defineAsyncComponent(() => import("@/components/ui/UiMap.vue"));
const LMarker = defineAsyncComponent(() => import("@/components/map").then(module => module.LMarker));
const LIcon = defineAsyncComponent(() => import("@/components/map").then(module => module.LIcon));

const repository = useService(ProviderRepository.service);
const timeZone = useDisplayTimeZone();

const { state: providers } = useAsyncState(repository.getAllProviders(), []);

onMounted(() => {
    document.querySelector<HTMLLinkElement>('link[rel="manifest"]').href = "/manifest.json";
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.chooser {
    inline-size: 100%;
}

.picker {
    @apply ui-popup;
    margin: --spacing(12);
    padding: --spacing(4);

    @variant max-md {
        position: absolute;
        inset-block-end: 0;
        inset-inline: 0;
        margin: --spacing(6);
    }
}

.heading {
    margin-block-end: --spacing(4);
    font-size: var(--text-xl);
    font-weight: bold;
}

.providers {
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: var(--text-base);

    @variant max-md {
        /* a few providers, then it scrolls */
        max-block-size: 170px;
    }

    & .provider {
        margin-inline: calc(--spacing(4) * -1);
        padding: --spacing(2) --spacing(4);

        &:hover {
            background: var(--color-light);
        }
    }
}

.provider {
    display: flex;
    align-items: center;
    gap: --spacing(2);

    &:hover {
        text-decoration: none;
    }
}

.provider-icon {
    font-size: 2em;
}

.short-name {
    font-weight: bold;
}

.name {
    color: var(--color-dark);
    font-size: 0.9em;
}

/* a provider's label on the map, as a Leaflet marker */
.label-box {
    @apply ui-popup;
    display: flex;
    align-items: center;
    gap: --spacing(2);
    min-inline-size: max-content;
    padding: --spacing(2);
    font-size: var(--text-xs);
    font-weight: bold;
    transform-origin: 50% 50%;
    translate: -50% 0;
    transition: scale var(--duration-fast) var(--ease-in-out);

    &:is(:hover, :active, :focus) {
        scale: 1.1;
    }
}

.marker-icon {
    font-size: var(--text-2xl);
}
</style>
