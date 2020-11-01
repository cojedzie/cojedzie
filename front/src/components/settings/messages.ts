import { Component } from "vue-property-decorator";
import store, { MessagesSettings } from "../../store";
import Vue from "vue";
import { MessagesSettingsState } from "../../store/settings/messages";

@Component({template: require("../../../templates/settings/messages.html"), store})
export class SettingsMessages extends Vue {
    @MessagesSettings.State
    public autorefresh: boolean;

    @MessagesSettings.State
    public autorefreshInterval: number;

    @MessagesSettings.State
    public displayedEntriesCount: number;

    @MessagesSettings.Mutation
    public update: (state: Partial<MessagesSettingsState>) => void;
}

Vue.component('SettingsMessages', SettingsMessages);
