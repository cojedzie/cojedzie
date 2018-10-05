import Vue from 'vue'
import store from '../store'
import { Component, Watch } from "vue-property-decorator";
import { Mutation, Action } from 'vuex-class'
import { ObtainPayload } from "../store/departures";
import { Stop } from "../model";

@Component({ store })
export class Application extends Vue {
    private sections = {
        messages: true
    };

    private settings = {
        messages: false,
        departures: false
    };

    private autorefresh = {
        messages: {
            active: true,
            interval: 60
        },
        departures: {
            active: true,
            interval: 10
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

    @Action('messages/update') updateMessages: () => void;
    @Action('departures/update') updateDepartures: (payload: ObtainPayload) => void;

    @Mutation add: (stops: Stop[]) => void;
    @Mutation remove: (stop: Stop) => void;
    @Mutation clear: () => void;

    save() {
        this.$store.dispatch('save').then(x => console.log(x));
    }

    @Watch('stops')
    onStopUpdate(this: any, stops) {
        this.updateDepartures({ stops });
    }

    @Watch('settings', { immediate: true, deep: true })
    onAutorefreshUpdate(settings) {
        if (this.intervals.messages) {
            clearInterval(this.intervals.messages);
            this.intervals.messages = null;
        }

        if (this.intervals.departures) {
            clearInterval(this.intervals.departures);
            this.intervals.messages = null;
        }

        if (settings.messages.active) {
            this.intervals.messages = setInterval(() => this.updateMessages(), Math.max(5, settings.messages.interval) * 1000);
        }

        if (settings.departures.active) {
            this.intervals.departures = setInterval(() => this.updateDepartures({ stops: this.stops }), Math.max(5, settings.departures.interval) * 1000);
        }
    }
}