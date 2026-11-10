<template>
    <div class="page-container board-page">
        <div class="columns">
            <div class="column board">
                <ui-section v-show="messages.count > 0" class="messages" icon="messages" title="Komunikaty" collapsible>
                    <template #title="{ icon, title }">
                        <ui-icon :icon="icon" fixed-width class="title-icon" />
                        {{ title }}
                        <span class="badge badge-pill badge-dark count">{{ messages.count }}</span>
                    </template>
                    <template #actions>
                        <ui-section-settings>
                            <settings-messages />
                        </ui-section-settings>
                        <button class="btn btn-action" @click="messages.update()">
                            <ui-tooltip>odśwież</ui-tooltip>
                            <ui-icon icon="refresh" :spin="messages.state === 'fetching'" fixed-width />
                        </button>
                    </template>
                    <messages-list />
                </ui-section>
                <ui-section icon="timetable" title="Odjazdy">
                    <template #actions>
                        <ui-section-settings>
                            <settings-departures />
                        </ui-section-settings>
                        <button class="btn btn-action" @click="departures.update()">
                            <ui-tooltip>odśwież</ui-tooltip>
                            <ui-icon icon="refresh" :spin="departures.state === 'fetching'" fixed-width />
                        </button>
                    </template>
                    <departures-list v-if="stops.length > 0" />
                    <div v-else class="alert alert-info">
                        <ui-icon icon="info" />
                        Wybierz przystanki korzystając z wyszukiwarki poniżej, aby zobaczyć listę odjazdów.
                    </div>
                    <div v-if="provider && provider.attribution" class="attribution">
                        <ui-icon icon="info" />
                        Pochodzenie danych:
                        <span v-html="provider.attribution" />
                    </div>
                </ui-section>
            </div>
            <div class="column side">
                <ui-section v-if="stops.length > 0" class="picker" icon="stop" title="Przystanki">
                    <template #actions>
                        <button class="btn btn-action" @click="selection.clear()">
                            <ui-tooltip>usuń wszystkie</ui-tooltip>
                            <ui-icon icon="delete" fixed-width />
                        </button>
                    </template>

                    <ul class="picked-stops">
                        <li v-for="stop in stops" :key="stop.id">
                            <stop-picker-entry :stop="stop">
                                <template #primary-action>
                                    <button class="btn btn-action" @click="selection.remove(stop)">
                                        <ui-tooltip>usuń przystanek</ui-tooltip>
                                        <ui-icon icon="remove-stop" />
                                    </button>
                                </template>
                            </stop-picker-entry>
                        </li>
                    </ul>

                    <div class="save-row">
                        <button ref="save" class="btn btn-action btn-sm" @click="visibility.save = true">
                            <ui-icon icon="favourite" fixed-width />
                            zapisz jako...
                        </button>
                    </div>

                    <ui-dialog
                        v-if="visibility.save"
                        :reference="saveButton"
                        arrow
                        placement="bottom-end"
                        title="Dodaj do ulubionych"
                        @leave="visibility.save = false"
                    >
                        <favourites-adder @saved="visibility.save = false" />
                    </ui-dialog>
                </ui-section>
                <ui-section
                    class="picker"
                    :icon="visibility.picker === 'search' ? 'search' : 'favourite'"
                    :title="visibility.picker === 'search' ? 'Wybierz przystanki' : 'Zapisane'"
                >
                    <template #actions>
                        <button
                            v-if="visibility.picker === 'search'"
                            class="btn btn-action"
                            @click="visibility.picker = 'favourites'"
                        >
                            <ui-tooltip>Zapisane</ui-tooltip>
                            <ui-icon icon="favourite" fixed-width />
                        </button>
                        <button v-else class="btn btn-action" @click="visibility.picker = 'search'">
                            <ui-tooltip>Wybierz przystanki</ui-tooltip>
                            <ui-icon icon="search" fixed-width />
                        </button>
                    </template>
                    <div class="switcher">
                        <transition name="fade">
                            <stop-picker
                                v-if="visibility.picker === 'search'"
                                :blacklist="stops"
                                @select="selection.add"
                            />
                            <favourites-list v-else-if="visibility.picker === 'favourites'" />
                        </transition>
                    </div>
                </ui-section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, useTemplateRef, watch, watchEffect } from "vue";
import { useRoute } from "vue-router";
import { storeToRefs } from "pinia";
import { prepare } from "@/api/utils";
import { useSelectionStore } from "@/stores/selection";
import { useDeparturesStore } from "@/stores/departures";
import { useMessagesStore } from "@/stores/messages";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useMessagesSettingsStore } from "@/stores/messages-settings";
import StopPickerEntry from "@/components/stop-picker/StopPickerEntry.vue";
import SettingsDepartures from "@/components/settings/SettingsDepartures.vue";
import SettingsMessages from "@/components/settings/SettingsMessages.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiSection from "@/components/ui/UiSection.vue";
import UiSectionSettings from "@/components/ui/UiSectionSettings.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import DeparturesList from "@/components/departures/DeparturesList.vue";
import FavouritesList from "@/components/favourites/FavouritesList.vue";
import FavouritesAdder from "@/components/favourites/FavouritesAdder.vue";
import MessagesList from "@/components/messages/MessagesList.vue";
import StopPicker from "@/components/stop-picker/StopPicker.vue";

defineOptions({ name: "MainPage" });

const route = useRoute();

const selection = useSelectionStore();
const departures = useDeparturesStore();
const messages = useMessagesStore();

const { stops, provider } = storeToRefs(selection);

const visibility = reactive({
    save: false,
    picker: "search",
});

const saveButton = useTemplateRef<HTMLButtonElement>("save");

// the refresh starts once the provider is known, requests depend on it
const providerLoaded = ref(false);

function useAutorefresh(settings: { autorefresh: boolean; autorefreshInterval?: number }, update: () => Promise<void>) {
    watchEffect(onCleanup => {
        if (!providerLoaded.value || !settings.autorefresh) {
            return;
        }

        const interval = setInterval(() => update(), Math.max(5, settings.autorefreshInterval) * 1000);
        onCleanup(() => clearInterval(interval));
    });
}

useAutorefresh(useDeparturesSettingsStore(), () => departures.update());
useAutorefresh(useMessagesSettingsStore(), () => messages.update());

watch(stops, () => departures.update(), { deep: true });

onMounted(() => {
    document.querySelector<HTMLLinkElement>('link[rel="manifest"]').href = prepare("/{provider}/manifest.json", {
        provider: route.params.provider as string,
    });
});

// the page mounts anew for every provider, what is left from the previous one must not show on this board
if (selection.provider && selection.provider.id !== route.params.provider) {
    messages.messages = [];
    departures.departures = [];
}

(async () => {
    await selection.loadProvider(route.params.provider as string);

    messages.update();
    departures.update();

    providerLoaded.value = true;
})();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.board-page {
    margin-block-start: --spacing(4);

    @variant md {
        padding-block-start: --spacing(8);
    }
}

.columns {
    display: flex;
    flex-wrap: wrap;
    margin-inline: calc(--spacing(4) * -1);
}

.column {
    inline-size: 100%;
    padding-inline: --spacing(4);
}

.board {
    @variant md {
        order: 2;
        inline-size: calc(100% * 2 / 3);
    }
}

.side {
    @variant md {
        order: 1;
        inline-size: calc(100% / 3);
    }
}

.title-icon {
    margin-inline-end: --spacing(1);
}

.count {
    margin-inline-start: --spacing(2);
}

.attribution {
    margin-block-start: --spacing(2);
    color: var(--color-muted);
    font-size: var(--text-sm);
    text-align: end;
}

.picked-stops {
    @apply list-underlined;
}

.save-row {
    display: flex;
    justify-content: flex-end;
    margin-block-start: --spacing(2);
}

/* the search and the saved stops cross-fade in one place */
.switcher {
    display: grid;

    & > * {
        grid-area: 1 / 1;
        min-block-size: 2px;
    }
}
</style>
