import Component from "vue-class-component";
import Vue from "vue";
import { Line, StopGroup, StopGroups, StopWithDestinations as Stop } from "../model";
import { Prop, Watch } from "vue-property-decorator";
import { FetchingState, filter, map, match, unique } from "@/utils";
import { debounce } from "@/decorators";
import { Mutation } from "vuex-class";
import { HistoryEntry } from "@/store/history";
import { StopHistory } from "./history";
import api from "@/api";

@Component({ template: require('@templates/picker/stop.html') })
export class PickerStopComponent extends Vue {
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

        const groupLinesByType = (lines: Line[]) => lines.reduce<{ [kind: string]: Line[]}>((groups, line) => ({
            ...groups,
            [line.type]: [ ...(groups[line.type] || []), line ]
        }), {});

        const joinedSymbol = match<string, [Line[]]>(
            [lines => lines.length === 1, lines => lines[0].symbol],
            [lines => lines.length === 2, ([first, second]) => `${first.symbol}, ${second.symbol}`],
            [lines => lines.length > 2,   ([first]) => `${first.symbol}…`],
        );

        return unique(this.stop.destinations || [], destination => destination.stop && destination.stop.name).map(compactLines);
    }
}

@Component({
    template: require('@templates/finder.html'),
    components: {
        "PickerStop": PickerStopComponent,
        "StopHistory": StopHistory,
    }
})
export class FinderComponent extends Vue {
    protected found?: StopGroups = {};

    public state: FetchingState = 'ready';
    public filter: string = "";

    @Prop({default: [], type: Array})
    public blacklist: Stop[];

    @Mutation('history/push') pushToHistory: (entry: HistoryEntry) => void;

    get filtered(): StopGroups {
        const groups = map(
            this.found,
            (group: StopGroup) =>
                group.filter(stop => !this.blacklist.some(blacklisted => blacklisted.id === stop.id))
        ) as StopGroups;

        return filter(groups, group => group.length > 0);
    }

    @Watch('filter')
    @debounce(400)
    async fetch() {
        if (this.filter.length < 3) {
            return;
        }

        this.state = 'fetching';
        try {
            const response = await api.get('v1_stop_groups', {
                query: {
                    name: this.filter,
                    'include-destinations': true
                },
                version: "1.0",
            });

            this.found = response.data.reduce((accumulator, { name, stops }) => Object.assign(accumulator, { [name]: stops }), {});
            this.state = 'ready';
        } catch (error) {
            this.state = 'error';
        }
    }

    private select(stop) {
        this.pushToHistory({
            date: this.$moment(),
            stop: stop,
        })

        this.$emit('select', stop);
    }
}

Vue.component('StopFinder', FinderComponent);
Vue.component('PickerStop', PickerStopComponent);
