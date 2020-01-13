import Vue from 'vue'
import { Departure, Stop } from "../model";
import { Component, Prop, Watch } from "vue-property-decorator";
import { namespace } from 'vuex-class';
import store from '../store'

const { State } = namespace('departures');

@Component({ template: require("../../components/departures.html"), store })
export class Departures extends Vue {
    @State
    departures: Departure[];

    @Prop(Array)
    stops: Stop[];
}

Vue.component('Departures', Departures);
