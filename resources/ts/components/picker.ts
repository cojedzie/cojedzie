import Component from "vue-class-component";
import Vue from "vue";
import { Stop, StopGroup, StopGroups } from "../model";
import { Prop, Watch } from "vue-property-decorator";
import { ensureArray, FetchingState, filter, map } from "../utils";
import { debounce } from "../decorators";
import urls from '../urls';

@Component({ template: require("../../components/picker.html") })
export class PickerComponent extends Vue {
    @Prop({ default: () => [], type: Array })
    protected stops?: Stop[];

    private remove(stop: Stop) {
        this.$emit('update:stops', this.stops.filter(s => s != stop));
    }

    private add(stop: Stop|Stop[]) {
        this.$emit('update:stops', [...this.stops, ...ensureArray(stop)]);
    }
}

@Component({ template: require('../../components/finder.html') })
export class FinderComponent extends Vue {
    protected found?: StopGroups = {};

    public state: FetchingState = 'ready';
    public filter: string = "";

    @Prop({default: [], type: Array})
    public blacklist: Stop[];

    get filtered(): StopGroups {
        const groups = map(
            this.found,
            (group: StopGroup, name: string) =>
                group.filter(stop => !this.blacklist.some(blacklisted => blacklisted.id == stop.id))
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

        const response = await fetch(urls.prepare(urls.stops.search, { name: this.filter }));

        if (response.ok) {
            this.found = await response.json();
            this.state = 'ready';
        } else {
            this.state = 'error';
        }
    }

    private select(stop) {
        this.$emit('select', stop);
    }
}

Vue.component('StopPicker', PickerComponent);
Vue.component('StopFinder', FinderComponent);
