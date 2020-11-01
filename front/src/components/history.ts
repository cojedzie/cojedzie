import Component from "vue-class-component";
import Vue from "vue";
import { History } from "../store";
import { HistoryEntry } from "../store/history";
import { Mutation } from "vuex-class";
import { Stop } from "../model";

@Component({ template: require('../../templates/stop/history.html' )})
export class StopHistory extends Vue {
    @History.Getter all: HistoryEntry[];

    @Mutation("add") select: (stops: Stop[]) => void;
}
