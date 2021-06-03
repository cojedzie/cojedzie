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

import { Component } from "vue-property-decorator";
import store, { DeparturesSettings } from "../../store";
import Vue from "vue";
import { DeparturesSettingsState } from "@/store/settings/departures";

@Component({ template: require("@templates/settings/departures.html"), store })
export class SettingsDepartures extends Vue {
    @DeparturesSettings.State
    public autorefresh: boolean;

    @DeparturesSettings.State
    public relativeTimes: boolean;

    @DeparturesSettings.State
    public autorefreshInterval: number;

    @DeparturesSettings.State
    public displayedEntriesCount: number;

    @DeparturesSettings.Mutation
    public update: (state: Partial<DeparturesSettingsState>) => void;
}

Vue.component('SettingsDepartures', SettingsDepartures);
