import Vue from 'vue'
import store from '../store'
import { Component, Watch } from "vue-property-decorator";
import { Mutation, Action } from 'vuex-class'
import { Stop } from "../model";
import { DeparturesSettingsState } from "../store/settings/departures";

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

    private autorefresh = {
        messages: {
            active: true,
            interval: 60
        }
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

    @Action('messages/update')   updateMessages: () => void;
    @Action('departures/update') updateDepartures: () => void;

    @Mutation add: (stops: Stop[]) => void;
    @Mutation remove: (stop: Stop) => void;
    @Mutation clear: () => void;

    @Watch('stops')
    onStopUpdate() {
        this.updateDepartures();
    }

    @Watch('autorefresh', { immediate: true, deep: true })
    onAutorefreshUpdate(settings) {
        if (this.intervals.messages) {
            clearInterval(this.intervals.messages);
            this.intervals.messages = null;
        }

        if (settings.messages.active) {
            this.intervals.messages = setInterval(() => this.updateMessages(), Math.max(5, settings.messages.interval) * 1000);
        }
    }
}
