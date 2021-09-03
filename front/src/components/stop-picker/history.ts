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
import { History } from "@/store";
import { HistoryEntry } from "@/store/modules/history";
import { Mutation } from "vuex-class";
import { Stop } from "@/model";
import { StopPickerEntry } from "@/components/stop-picker/entry";
import WithRender from "@templates/stop-picker/history.html";

@WithRender
@Options({
    name: "StopPickerHistory",
    components: { StopPickerEntry }
})
export class StopPickerHistory extends Vue {
    @History.Getter all: HistoryEntry[];

    @Mutation("add") select: (stops: Stop[]) => void;
}

export default StopPickerHistory;
