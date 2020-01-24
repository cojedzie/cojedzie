import Vue from "vue";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { ScheduledStop } from "../model/trip";

@Component({ template: require("../../components/trip.html") })
export class TripComponent extends Vue {
    @Prop(Array) public schedule: ScheduledStop[];
}

Vue.component('Trip', TripComponent);
