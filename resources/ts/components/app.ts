import Vue from 'vue'
import store from '../store'
import { Component, Watch } from "vue-property-decorator";
import { Mutation, Action } from 'vuex-class'
import { Stop } from "../model";
import { DeparturesSettingsState } from "../store/settings/departures";
import { MessagesSettingsState } from "../store/settings/messages";

@Component({ store })
export class Application extends Vue {
    private sections = {
        messages: true
    };

    private visibility = {
        messages: false,
        departures: false,
        save: false,
        picker: 'search'
    };

    private intervals = { messages: null, departures: null };

    get messages() {
        return {
            count: this.$store.getters['messages/count'],
            counts: this.$store.getters['messages/counts'],
            state: this.$store.state.messages.state
        };
    }

    get departures() {
        return {
            state: this.$store.state.departures.state
        };
    }

    get stops() {
        return this.$store.state.stops;
    }

    set stops(value) {
        this.$store.commit('updateStops', value);
    }

    mounted() {
        this.$el.classList.remove('not-ready');
    }

    created() {
        this.initDeparturesRefreshInterval();
        this.initMessagesRefreshInterval();
    }

    private initDeparturesRefreshInterval() {
        const departuresAutorefreshCallback = () => {
            const {autorefresh, autorefreshInterval} = this.$store.state['departures-settings'] as DeparturesSettingsState;

            if (this.intervals.departures) {
                clearInterval(this.intervals.departures);
            }

            if (autorefresh) {
                this.intervals.departures = setInterval(() => this.updateDepartures(), Math.max(5, autorefreshInterval) * 1000)
            }
        };

        this.$store.watch(({"departures-settings": state}) => state.autorefresh, departuresAutorefreshCallback);
        this.$store.watch(({"departures-settings": state}) => state.autorefreshInterval, departuresAutorefreshCallback);

        departuresAutorefreshCallback();
    }

    private initMessagesRefreshInterval() {
        const messagesAutorefreshCallback = () => {
            const {autorefresh, autorefreshInterval} = this.$store.state['messages-settings'] as MessagesSettingsState;

            if (this.intervals.messages) {
                clearInterval(this.intervals.messages);
            }

            if (autorefresh) {
                this.intervals.messages = setInterval(() => this.updateMessages(), Math.max(5, autorefreshInterval) * 1000)
            }
        };

        this.$store.watch(({"messages-settings": state}) => state.autorefresh, messagesAutorefreshCallback);
        this.$store.watch(({"messages-settings": state}) => state.autorefreshInterval, messagesAutorefreshCallback);

        messagesAutorefreshCallback();
    }

    @Action('messages/update')   updateMessages: () => void;
    @Action('departures/update') updateDepartures: () => void;

    @Mutation add: (stops: Stop[]) => void;
    @Mutation remove: (stop: Stop) => void;
    @Mutation clear: () => void;

    @Watch('stops')
    onStopUpdate() {
        this.updateDepartures();
    }
}
