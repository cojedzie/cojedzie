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
import { Line, StopWithDestinations as Stop } from "@/model";
import { match, unique } from "@/utils";
import WithRender from "@templates/stop-picker/entry.html";

@WithRender
@Options({ name: "StopPickerEntry" })
export class StopPickerEntry extends Vue {
    @Prop(Object)
    public stop: Stop;

    details: boolean = false;
    map: boolean = false;
    inMap: boolean = false;

    get showMap() {
        return this.inMap || this.map;
    }

    get destinations() {
        const compactLines = destination => ({
            ...destination,
            lines: Object.entries(groupLinesByType(destination.lines || [])).map(([type, lines]) => ({
                type: type,
                symbol: joinedSymbol(lines),
                night: lines.every(line => line.night),
                fast: lines.every(line => line.fast),
            })),
            all: destination.lines
        });

        const groupLinesByType = (lines: Line[]) => lines.reduce<{ [kind: string]: Line[] }>((groups, line) => ({
            ...groups,
            [line.type]: [...(groups[line.type] || []), line]
        }), {});

        const joinedSymbol = match<string, [Line[]]>(
            [lines => lines.length === 1, lines => lines[0].symbol],
            [lines => lines.length === 2, ([first, second]) => `${ first.symbol }, ${ second.symbol }`],
            [lines => lines.length > 2, ([first]) => `${ first.symbol }…`],
        );

        return unique(this.stop.destinations || [], destination => destination.stop && destination.stop.name).map(compactLines);
    }
}

export default StopPickerEntry;
