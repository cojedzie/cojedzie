<template>
    <div id="app" class="container">
        <div class="row">
            <div class="col-md-8 order-md-last">
                <section v-show="messages.count > 0" class="section messages">
                    <header class="section__title flex">
                        <h2>
                            <ui-icon icon="messages" fixed-width class="mr-2" />
                            Komunikaty
                            <span class="ml-2 badge badge-pill badge-dark">{{ messages.count }}</span>
                        </h2>
                        <button
                            id="settings-messages"
                            ref="settings-messages"
                            class="btn btn-action flex-space-left"
                            @click="visibility.messages = !visibility.messages"
                        >
                            <ui-tooltip>ustawienia</ui-tooltip>
                            <ui-icon icon="settings" fixed-width />
                        </button>
                        <button ref="btn-messages-refresh" class="btn btn-action" @click="updateMessages">
                            <ui-tooltip>odśwież</ui-tooltip>
                            <ui-icon icon="refresh" :spin="messages.state === 'fetching'" fixed-width />
                        </button>
                        <button class="btn btn-action" @click="sections.messages = !sections.messages">
                            <ui-tooltip>
                                {{ sections.messages ? "zwiń" : "rozwiń" }}
                                <span class="sr-only">sekcję komunikatów</span>
                            </ui-tooltip>
                            <ui-icon :icon="sections.messages ? 'chevron-up' : 'chevron-down'" fixed-width />
                        </button>

                        <teleport to="#popups">
                            <ui-dialog
                                v-if="visibility.messages"
                                reference="#settings-messages"
                                arrow
                                placement="left-start"
                                @leave="visibility.messages = false"
                            >
                                <settings-messages />
                            </ui-dialog>
                        </teleport>
                    </header>
                    <ui-fold :visible="sections.messages">
                        <messages-list />
                    </ui-fold>
                </section>
                <section class="section">
                    <header class="section__title flex">
                        <h2>
                            <ui-icon icon="timetable" fixed-width />
                            <span class="text">Odjazdy</span>
                        </h2>

                        <button
                            id="settings-departures"
                            ref="settings-departures"
                            class="btn btn-action flex-space-left"
                            @click="visibility.departures = !visibility.departures"
                        >
                            <ui-tooltip>ustawienia</ui-tooltip>
                            <ui-icon icon="settings" fixed-width />
                        </button>
                        <button class="btn btn-action" @click="updateDepartures({ stops })">
                            <ui-tooltip>odśwież</ui-tooltip>
                            <ui-icon icon="refresh" :spin="departures.state === 'fetching'" fixed-width />
                        </button>
                        <teleport to="#popups">
                            <ui-dialog
                                v-if="visibility.departures"
                                reference="#settings-departures"
                                arrow
                                placement="left-start"
                                @leave="visibility.departures = false"
                            >
                                <settings-departures />
                            </ui-dialog>
                        </teleport>
                    </header>
                    <departures-list v-if="stops.length > 0" :stops="stops" />
                    <div v-else class="alert alert-info">
                        <ui-icon icon="info" />
                        Wybierz przystanki korzystając z wyszukiwarki poniżej, aby zobaczyć listę odjazdów.
                    </div>
                    <div v-if="provider && provider.attribution" class="attribution">
                        <ui-icon icon="info" />
                        Pochodzenie danych:
                        <span class="attribution__attribution" v-html="provider.attribution" />
                    </div>
                </section>
            </div>
            <div class="col-md-4 order-md-first">
                <section v-if="stops.length > 0" class="section picker">
                    <header class="section__title flex">
                        <h2>
                            <ui-icon icon="stop" fixed-width />
                            <span class="text">Przystanki</span>
                        </h2>
                        <button class="btn btn-action flex-space-left" @click="clear">
                            <ui-tooltip>usuń wszystkie</ui-tooltip>
                            <ui-icon icon="delete" fixed-width />
                        </button>
                    </header>

                    <ul class="picker__stops list-underlined">
                        <li v-for="stop in stops" :key="stop.id" class="picker__stop">
                            <stop-picker-entry :stop="stop">
                                <template #primary-action>
                                    <button class="btn btn-action" @click="remove(stop)">
                                        <ui-tooltip>usuń przystanek</ui-tooltip>
                                        <ui-icon icon="remove-stop" />
                                    </button>
                                </template>
                            </stop-picker-entry>
                        </li>
                    </ul>

                    <div class="d-flex mt-2">
                        <button
                            ref="save"
                            class="btn btn-action btn-sm flex-space-left"
                            @click="visibility.save = true"
                        >
                            <ui-icon icon="favourite" fixed-width />
                            zapisz jako...
                        </button>
                    </div>

                    <ui-dialog
                        v-if="visibility.save"
                        reference="save"
                        arrow
                        placement="bottom-end"
                        title="Dodaj do ulubionych"
                        @leave="visibility.save = false"
                    >
                        <favourites-adder @saved="visibility.save = false" />
                    </ui-dialog>
                </section>
                <section class="section picker">
                    <header class="section__title flex">
                        <template v-if="visibility.picker === 'search'">
                            <h2 class="flex-grow-1">
                                <ui-icon icon="search" fixed-width class="mr-1" />
                                Wybierz przystanki
                            </h2>
                            <button class="btn btn-action" @click="visibility.picker = 'favourites'">
                                <ui-tooltip>Zapisane</ui-tooltip>
                                <ui-icon icon="favourite" fixed-witdth />
                            </button>
                        </template>
                        <template v-else>
                            <h2 class="flex-grow-1">
                                <ui-icon icon="favourite" fixed-width class="mr-1" />
                                Zapisane
                            </h2>
                            <button class="btn btn-action" @click="visibility.picker = 'search'">
                                <ui-tooltip>Wybierz przystanki</ui-tooltip>
                                <ui-icon icon="search" fixed-witdth />
                            </button>
                        </template>
                    </header>
                    <div class="transition-box">
                        <transition name="fade">
                            <stop-picker v-if="visibility.picker === 'search'" :blacklist="stops" @select="add" />
                            <favourites-list v-else-if="visibility.picker === 'favourites'" />
                        </transition>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { Watch } from "vue-property-decorator";
import { Action, Mutation, State } from "vuex-class";
import { Provider, Stop } from "@/model";
import { DeparturesSettingsState } from "@/store/modules/settings/departures";
import { MessagesSettingsState } from "@/store/modules/settings/messages";
import { prepare } from "@/api/utils";
import { MessagesActions } from "@/store/modules/messages";
import { StoreDefinition } from "@/store/initializer";
import { Store } from "vuex";
import { Options, Vue } from "vue-class-component";
import { StopPickerEntry } from "@/components";

@Options({
    name: "MainPage",
    components: { StopPickerEntry },
})
export default class MainPage extends Vue {
    $store: Store<StoreDefinition>;

    private sections = {
        messages: true,
    };

    private visibility = {
        messages: false,
        departures: false,
        save: false,
        picker: "search",
    };

    private intervals = { messages: null, departures: null };

    @State private provider: Provider;

    get messages() {
        return {
            count: this.$store.getters["messages/count"],
            counts: this.$store.getters["messages/counts"],
            state: this.$store.state.messages.state,
        };
    }

    get departures() {
        return {
            state: this.$store.state.departures.state,
        };
    }

    get stops() {
        return this.$store.state.stops;
    }

    set stops(value) {
        this.$store.commit("replace", value);
    }

    mounted() {
        document.querySelector<HTMLLinkElement>('link[rel="manifest"]').href = prepare("/{provider}/manifest.json", {
            provider: this.$route.params.provider as string,
        });
    }

    async created() {
        await this.$store.dispatch("loadProvider", {
            provider: this.$route.params.provider as string,
        });

        this.$store.dispatch(`messages/${MessagesActions.Update}`);
        this.$store.dispatch("load", { version: 1, stops: [] });

        this.initDeparturesRefreshInterval();
        this.initMessagesRefreshInterval();
    }

    private initDeparturesRefreshInterval() {
        const departuresAutorefreshCallback = () => {
            const { autorefresh, autorefreshInterval } = this.$store.state[
                "departures-settings"
            ] as DeparturesSettingsState;

            if (this.intervals.departures) {
                clearInterval(this.intervals.departures);
            }

            if (autorefresh) {
                this.intervals.departures = setInterval(
                    () => this.updateDepartures(),
                    Math.max(5, autorefreshInterval) * 1000
                );
            }
        };

        this.$store.watch(({ "departures-settings": state }) => state.autorefresh, departuresAutorefreshCallback);
        this.$store.watch(
            ({ "departures-settings": state }) => state.autorefreshInterval,
            departuresAutorefreshCallback
        );

        departuresAutorefreshCallback();
    }

    private initMessagesRefreshInterval() {
        const messagesAutorefreshCallback = () => {
            const { autorefresh, autorefreshInterval } = this.$store.state[
                "messages-settings"
            ] as MessagesSettingsState;

            if (this.intervals.messages) {
                clearInterval(this.intervals.messages);
            }

            if (autorefresh) {
                this.intervals.messages = setInterval(
                    () => this.updateMessages(),
                    Math.max(5, autorefreshInterval) * 1000
                );
            }
        };

        this.$store.watch(({ "messages-settings": state }) => state.autorefresh, messagesAutorefreshCallback);
        this.$store.watch(({ "messages-settings": state }) => state.autorefreshInterval, messagesAutorefreshCallback);

        messagesAutorefreshCallback();
    }

    @Action(`messages/${MessagesActions.Update}`) updateMessages: () => void;
    @Action("departures/update") updateDepartures: () => void;

    @Mutation add: (stops: Stop[]) => void;
    @Mutation remove: (stop: Stop) => void;
    @Mutation clear: () => void;

    @Watch("stops", { deep: true })
    onStopUpdate() {
        this.updateDepartures();
    }
}
</script>
