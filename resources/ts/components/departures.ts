import Vue from 'vue'
import { Departure, Stop } from "../model";
import { Component, Prop, Watch } from "vue-property-decorator";
import urls from '../urls';
import template = require("../../components/departures.html");
import moment = require("moment");
import { Jsonified } from "../utils";
import { debounce } from "../decorators";

@Component({ template })
export class Departures extends Vue {
    private _intervalId: number;

    autoRefresh: boolean    = false;
    departures: Departure[] = [];
    interval: number        = 20;

    @Prop(Array)
    stops: Stop[];

    @Watch('stops')
    @debounce(300)
    async update() {
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