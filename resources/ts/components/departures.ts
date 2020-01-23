import Vue from 'vue'
import { Departure, Stop } from "../model";
import { Component, Prop, Watch } from "vue-property-decorator";
import { namespace } from 'vuex-class';
import store from '../store'
import { Trip } from "../model/trip";
import urls from "../urls";

const { State } = namespace('departures');

@Component({ template: require("../../components/departures.html"), store })
export class DeparturesComponent extends Vue {
    @State departures: Departure[];

    @Prop(Array)
    stops: Stop[];
}

@Component({ template: require("../../components/departures/departure.html") })
export class DepartureComponent extends Vue {
    @Prop(Object) departure: Departure;

    showTrip: boolean = false;
    trip: Trip = null;

    get timeDiffers() {
        const departure = this.departure;

        return departure.estimated && departure.scheduled.format('HH:mm') !== departure.estimated.format('HH:mm');
    }

    get time() {
        return this.departure.estimated || this.departure.scheduled;
    }

    @Watch('showTrip')
    async downloadTrips() {
        if (this.showTrip != true || this.trip != null) {
            return;
        }

        const response = await fetch(urls.prepare(urls.trip, { id: this.departure.trip.id }));

        if (response.ok) {
            this.trip = await response.json();
        }
    }
}

Vue.component('Departures', DeparturesComponent);
Vue.component('Departure', DepartureComponent);
