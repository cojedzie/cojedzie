<template>
    <div class="trip">
        <ol ref="stops" v-dragscroll.x="!$isTouch" class="trip__stops">
            <li v-for="stop in stops" class="trip__stop" :class="[ stop.current && 'trip__stop--current', stop.visited && 'trip__stop--visited' ]">
                <div class="trip__marker" />
                <div class="trip__description">
                    <stop-label :stop="stop.stop" />
                </div>
                <div class="trip__departure">
                    {{ stop.departure.format('HH:mm') }}
                </div>
            </li>
        </ol>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { ScheduledStop } from "@/model/trip";
import { Stop } from "@/model";
import moment from "moment";

type ScheduledStopInfo = ScheduledStop & { visited: boolean, current: boolean };

@Options({ name: "TripSchedule" })
export default class TripSchedule extends Vue {
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
        const list = this.$refs['stops'] as HTMLUListElement;
        const current = list.querySelector('.trip__stop--current') as HTMLLIElement;

        if (!current) return;

        list.scrollLeft = current.offsetLeft - (list.clientWidth + current.clientWidth) / 2;
    }
}
</script>
