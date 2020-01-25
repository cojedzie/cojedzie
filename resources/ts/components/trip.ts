import Vue from "vue";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { ScheduledStop } from "../model/trip";
import { Stop } from "../model";
import * as moment from 'moment';

type ScheduledStopInfo = ScheduledStop & { visited: boolean, current: boolean };

@Component({ template: require("../../components/trip.html") })
export class TripComponent extends Vue {
    @Prop(Array) public schedule: ScheduledStop[];
    @Prop(Object) public current: Stop;

    get stops(): ScheduledStopInfo[] {
        return this.schedule.map(stop => ({
            ...stop,
            current: stop.stop.id == this.current.id,
            visited: moment().isAfter(stop.departure),
        }));
    }

    mounted() {
        const list    = this.$refs['stops'] as HTMLUListElement;
        const current = list.querySelector('.trip__stop--current') as HTMLLIElement;

        if (!current) return;

        list.scrollLeft = current.offsetLeft - (list.clientWidth + current.clientWidth) / 2;
    }
}

Vue.component('Trip', TripComponent);
