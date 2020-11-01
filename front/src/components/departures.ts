import Vue from 'vue'
import { Departure } from "@/model";
import { Component, Prop, Watch } from "vue-property-decorator";
import store, { Departures, DeparturesSettings } from '../store'
import { Trip } from "@/model/trip";
import urls from "../urls";
import { Jsonified } from "@/utils";
import * as moment from "moment";

@Component({ template: require("@templates/departures.html"), store })
export class DeparturesComponent extends Vue {
    @Departures.State departures: Departure[];
}

@Component({ template: require("@templates/departures/departure.html"), store })
export class DepartureComponent extends Vue {
    @Prop(Object) departure: Departure;
    scheduledTrip: Trip = null;

    @DeparturesSettings.State
    relativeTimes: boolean;

    showTrip: boolean = false;

    processTrip(trip: Jsonified<Trip>): Trip {
        return {
            ...trip,
            schedule: trip.schedule.map(s => ({
                ...s,
                arrival: moment.parseZone(s.arrival),
                departure: moment.parseZone(s.departure),
            }))
        };
    };

    get timeDiffers() {
        const departure = this.departure;

        return departure.estimated && departure.scheduled.format('HH:mm') !== departure.estimated.format('HH:mm');
    }

    get time() {
        return this.departure.estimated || this.departure.scheduled;
    }

    get timeLeft() {
        return moment.duration(this.time.diff(moment()));
    }

    @Watch('showTrip')
    async downloadTrips() {
        if (this.showTrip != true || this.trip != null) {
            return;
        }

        const response = await fetch(urls.prepare(urls.trip, { id: this.departure.trip.id }));

        if (response.ok) {
            this.scheduledTrip = this.processTrip(await response.json());
        }
    }

    get trip() {
        const trip = this.scheduledTrip;
        return trip && {
            ...trip,
            schedule: trip.schedule.map(stop => ({
                ...stop,
                arrival: stop.arrival.clone().add(this.departure.delay, 'seconds'),
                departure: stop.departure.clone().add(this.departure.delay, 'seconds'),
            }))
        };
    }
}

Vue.component('Departures', DeparturesComponent);
Vue.component('Departure', DepartureComponent);
