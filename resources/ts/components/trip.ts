import Vue from "vue";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { ScheduledStop } from "../model/trip";
import { Stop } from "../model";

type ScheduledStopInfo = ScheduledStop & { visited: boolean, current: boolean };

@Component({ template: require("../../components/trip.html") })
export class TripComponent extends Vue {
    @Prop(Array) public schedule: ScheduledStop[];
    @Prop(Object) public current: Stop;

    get stops(): ScheduledStopInfo[] {
        let visited = true;

        return this.schedule.map(stop => ({
            ...stop,
            current: stop.stop.id == this.current.id ? !(visited = false) : false,
            visited: visited,
        }));
    }
}

Vue.component('Trip', TripComponent);
