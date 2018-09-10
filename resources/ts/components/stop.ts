import { Component, Prop } from "vue-property-decorator";
import { Line, Stop, Track } from "../model";
import Vue from 'vue';

import template = require('../../components/stop.html');
import details  = require('../../components/stop-details.html');
import urls from "../urls";

@Component({ template: details })
class StopDetailsComponent extends Vue {
    @Prop(Object)
    public stop: Stop;

    private ready: boolean = false;

    tracks: { order: number, track: Track }[] = [];

    get types() {
        return this.tracks.map(t => t.track.line.type).filter((value, index, array) => {
            return array.indexOf(value) === index;
        });
    }

    get lines(): Line[] {
        return this.tracks.map(t => t.track.line).reduce((lines, line: Line) => {
            return Object.assign(lines, { [line.symbol]: line });
        }, {} as any);
    }

    async mounted() {
        const response = await fetch(urls.prepare(urls.stops.tracks, { id: this.stop.id }));

        if (response.ok) {
            this.tracks = await response.json();
        }

        this.ready = true;
    }
}

@Component({ template })
export class StopComponent extends Vue {
    @Prop(Object)
    public stop: Stop;

    details: boolean = false;
}

Vue.component('Stop', StopComponent);
Vue.component('StopDetails', StopDetailsComponent);
