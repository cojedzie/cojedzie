<template>
    <div class="form-group">
        <div class="label hstack">
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
        <div v-if="autorefresh" class="hstack">
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
                            autorefreshInterval: Number.parseInt(($event.target as HTMLInputElement).value),
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
        <div class="label hstack">
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

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useMessagesSettingsStore } from "@/stores/messages-settings";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiNumericInput from "@/components/ui/UiNumericInput.vue";
import UiSwitch from "@/components/ui/UiSwitch.vue";
import UiHelp from "@/components/ui/UiHelp.vue";

defineOptions({ name: "SettingsMessages" });

const store = useMessagesSettingsStore();
const { update } = store;
const { autorefresh, autorefreshInterval, displayedEntriesCount } = storeToRefs(store);
</script>
