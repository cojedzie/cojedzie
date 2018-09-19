import Vue from 'vue'
import { Departure, Stop } from "../model";
import { Component, Prop, Watch } from "vue-property-decorator";
import urls from '../urls';
import * as moment from "moment";
import { FetchingState, Jsonified } from "../utils";
import { debounce, notify } from "../decorators";

@Component({ template: require("../../components/departures.html") })
export class Departures extends Vue {
    private _intervalId: number;

    departures: Departure[] = [];

    @Prop(Array)
    stops: Stop[];

    @Prop({ default: false, type: Boolean })
    autoRefresh: boolean;

    @Prop({ default: 20, type: Number })
    interval: number;

    @notify()
    state: FetchingState;

    @Watch('stops')
    @debounce(300)
    async update() {
        this.state = 'fetching';
        const response = await fetch(urls.prepare(urls.departures, {
            stop: this.stops.map(stop => stop.id),
        }));

        if (response.ok) {
            const departures = await response.json() as Jsonified<Departure>[];

            this.departures = departures.map(departure => {
                departure.scheduled = moment.parseZone(departure.scheduled);
                departure.estimated = moment.parseZone(departure.estimated);

                return departure as Departure;
            });

            this.state = 'ready';
        } else {
            this.state = 'error';
        }
    }

    @Watch('interval')
    @Watch('autoRefresh')
    private setupAutoRefresh() {
        if (this._intervalId) {
            window.clearInterval(this._intervalId);
            this._intervalId = undefined;
        }

        if (this.autoRefresh) {
            this._intervalId = window.setInterval(() => this.update(), this.interval * 1000);
        }
    }
}

Vue.component('Departures', Departures);