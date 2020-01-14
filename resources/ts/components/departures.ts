import Vue from 'vue'
import { Departure, Stop } from "../model";
import { Component, Prop, Watch } from "vue-property-decorator";
import { namespace } from 'vuex-class';
import store from '../store'

const { State } = namespace('departures');

@Component({ template: require("../../components/departures.html"), store })
export class DeparturesComponent extends Vue {
    @State
    departures: Departure[];

    @Prop(Array)
    stops: Stop[];
}

@Component({ template: require("../../components/departures/departure.html"), store })
export class DepartureComponent extends Vue {
    @Prop(Object)
    departure: Departure;

    get timeDiffers() {
        const departure = this.departure;

        return departure.estimated && departure.scheduled.format('HH:mm') !== departure.estimated.format('HH:mm');
    }

    get time() {
        return this.departure.estimated || this.departure.scheduled;
    }
}

Vue.component('Departures', DeparturesComponent);
Vue.component('Departure', DepartureComponent);
