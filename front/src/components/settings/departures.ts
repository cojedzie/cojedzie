import { Component } from "vue-property-decorator";
import store, { DeparturesSettings } from "../../store";
import Vue from "vue";
import { DeparturesSettingsState } from "../../store/settings/departures";

@Component({ template: require("../../../templates/settings/departures.html"), store })
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
