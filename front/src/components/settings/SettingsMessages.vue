<template>
    <div class="form-group">
        <div class="label flex">
            <label class="text" for="departures-auto-refresh-interval">
                <ui-icon icon="refresh" fixed-width />
                autoodświeżanie
            </label>
            <ui-switch
                id="departures-auto-refresh"
                class="flex-space-left"
                :value="autorefresh"
                @update:value="update({ autorefresh: $event })"
            />
            <ui-help class="label__button">
                <template #title>
                    <ui-icon icon="refresh" fixed-width />
                    autoodświeżanie
                </template>

                <p>Automatyczne odświeżanie listy komunikatów z zadaną częstotliwością.</p>
                <p>
                    Zbyt częste odświeżanie listy komunikatów może prowadzić do większego zużycia energii bez
                    zwiększenia dokładności informacji.
                </p>
            </ui-help>
        </div>
        <div v-if="autorefresh" class="flex">
            <label for="departures-auto-refresh-interval" class="text">
                <span class="sr-only">częstotliwość odświeżania</span>
                co
            </label>
            <div class="input-group input-group-sm">
                <input
                    id="departures-auto-refresh-interval"
                    type="text"
                    class="form-control form-control-sm form-control-simple"
                    :value="autorefreshInterval"
                    @input="
                        update({
                            autorefreshInterval: Number.parseInt($event.target.value),
                        })
                    "
                />
                <div class="input-group-append">
                    <span class="input-group-text" aria-label="sekund">s</span>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="label flex">
            <label class="text" for="departures-count">
                <ui-icon icon="messages" fixed-width />
                wyświetlanych komunikatów
            </label>
            <ui-help class="flex-space-left label__button">
                <template #title>
                    <ui-icon icon="line-bus" fixed-width />
                    liczba wpisów
                </template>

                <p>Kontroluje liczbę wyświetlanych komunikatów.</p>
            </ui-help>
        </div>
        <ui-numeric-input
            id="departures-count"
            :min="1"
            :value="displayedEntriesCount"
            @update:value="update({ displayedEntriesCount: $event })"
        />
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import store, { MessagesSettings } from "@/store";
import { MessagesSettingsState } from "@/store/modules/settings/messages";

@Options({
    name: "SettingsMessages",
    store,
})
export default class SettingsMessages extends Vue {
    @MessagesSettings.State
    public autorefresh: boolean;

    @MessagesSettings.State
    public autorefreshInterval: number;

    @MessagesSettings.State
    public displayedEntriesCount: number;

    @MessagesSettings.Mutation
    public update: (state: Partial<MessagesSettingsState>) => void;
}
</script>
