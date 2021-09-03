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
import { Line, Stop, Track } from "@/model";
import api from "@/api";
import WithRender from "@templates/stop/details.html"

@WithRender
@Options({ name: "StopDetails" })
export class StopDetails extends Vue {
    @Prop(Object)
    public stop: Stop;

    private ready: boolean = false;

    tracks: { order: number, track: Track }[] = [];

    get types() {
        return this.tracks.map(t => t.track.line.type).filter((value, index, array) => {
            return array.indexOf(value) === index;
        });
    }

    get lines(): Line[] {
        return this.tracks.map(t => t.track.line).reduce((lines, line: Line) => {
            return Object.assign(lines, { [line.symbol]: line });
        }, {} as any);
    }

    async mounted() {
        const response = await api.get('v1_stop_tracks', {
            params: { stop: this.stop.id },
            version: "^1.0",
        });

        // fixme: this as any should not be needed
        this.tracks = response.data as any;
        this.ready = true;
    }
}
