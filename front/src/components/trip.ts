/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { ScheduledStop } from "@/model/trip";
import { Stop } from "@/model";
import moment from 'moment';
import { application } from "express";
import { app } from "@/components/application";

type ScheduledStopInfo = ScheduledStop & { visited: boolean, current: boolean };

@Options({ render: require("@templates/trip.html").render })
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

app.component('Trip', TripComponent);
