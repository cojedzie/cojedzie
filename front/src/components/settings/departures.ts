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
import store, { DeparturesSettings } from "../../store";
import { DeparturesSettingsState } from "@/store/modules/settings/departures";
import WithRender from "@templates/settings/departures.html";

@WithRender
@Options({
    name: "SettingsDepartures",
    store
})
export class SettingsDepartures extends Vue {
    @DeparturesSettings.State
    public autorefresh: boolean;
    @DeparturesSettings.State
    public relativeTimes: boolean;
    @DeparturesSettings.State
    public relativeTimesForScheduled: boolean;
    @DeparturesSettings.State
    public autorefreshInterval: number;
    @DeparturesSettings.State
    public displayedEntriesCount: number;

    public get relativeTimesLimit(): number {
        return this.$store.state["departures-settings"].relativeTimesLimit;
    }

    public set relativeTimesLimit(relativeTimesLimit: number | string) {
        this.update({
            relativeTimesLimit: typeof relativeTimesLimit === "string" ? Number.parseInt(relativeTimesLimit) : relativeTimesLimit
        })
    }

    public get relativeTimesHasLimit(): boolean {
        return this.$store.state["departures-settings"].relativeTimesLimitEnabled;
    }

    public set relativeTimesHasLimit(relativeTimesLimitEnabled: boolean) {
        this.update({ relativeTimesLimitEnabled })
    }

    @DeparturesSettings.Mutation
    public update: (state: Partial<DeparturesSettingsState>) => void;

    public relativeTimesShowAdvancedOptions: boolean = false;
}

export default SettingsDepartures;
