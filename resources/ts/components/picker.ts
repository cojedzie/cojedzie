import Component from "vue-class-component";
import Vue from "vue";
import { Stop, StopGroup, StopGroups } from "../model";
import { Prop, Watch } from "vue-property-decorator";
import { FetchingState, filter, map, unique } from "../utils";
import { debounce } from "../decorators";
import urls from '../urls';

@Component({ template: require('../../components/picker/stop.html') })
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
        return unique(this.stop.destinations, stop => stop.name);
    }
}

@Component({
    template: require('../../components/finder.html'),
    components: {
        "PickerStop": PickerStopComponent
    }
})
export class FinderComponent extends Vue {
    protected found?: StopGroups = {};

    public state: FetchingState = 'ready';
    public filter: string = "";

    @Prop({default: [], type: Array})
    public blacklist: Stop[];

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

        const response = await fetch(urls.prepare(urls.stops.grouped, { name: this.filter }));

        if (response.ok) {
            this.found = (await response.json()).reduce((accumulator, { name, stops }) => Object.assign(accumulator, { [name]: stops }), {});
            this.state = 'ready';
        } else {
            this.state = 'error';
        }
    }

    private select(stop) {
        this.$emit('select', stop);
    }
}

Vue.component('StopFinder', FinderComponent);
Vue.component('PickerStop', PickerStopComponent);
