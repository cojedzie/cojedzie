import Component from "vue-class-component";
import Vue from "vue";
import { Stop, StopGroup, StopGroups } from "../model";
import urls from '../urls';

import picker = require("../../components/picker.html");
import finder = require('../../components/finder.html');
import stop   = require('../../components/stop.html');

import { Prop, Watch } from "vue-property-decorator";
import { filter, map } from "../utils";
import { debounce } from "../decorators";

@Component({ template: picker })
export class PickerComponent extends Vue {
    protected stops?: Stop[] = [];

    private remove(stop: Stop) {
        this.stops = this.stops.filter(s => s != stop);
    }

    private add(stop: Stop) {
        this.stops.push(stop);
    }
}

type FinderState = 'fetching' | 'ready' | 'error';

@Component({ template: finder })
export class FinderComponent extends Vue {
    protected found?: StopGroups = {};

    public state: FinderState = 'ready';
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

@Component({ template: stop })
export class StopComponent extends Vue {
    @Prop(Object)
    public stop: Stop;
}

Vue.component('StopPicker', PickerComponent);
Vue.component('StopFinder', FinderComponent);
Vue.component('Stop', StopComponent);
