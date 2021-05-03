import { Component, Prop } from "vue-property-decorator";
import { Line, Stop, Track } from "../model";
import Vue from 'vue';
import api from "@/api";

@Component({ template: require('@templates/stop/details.html') })
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
        const response = await api.get('v1_stop_tracks', {
            params: { stop: this.stop.id },
            version: "^1.0",
        });

        // fixme: this as any should not be needed
        this.tracks = response.data as any;
        this.ready = true;
    }
}

@Component({ template: require('@templates/stop.html') })
export class StopComponent extends Vue {
    @Prop(Object)
    public stop: Stop;
}

@Component({ template: require('@templates/stop/map.html') })
export class StopMapComponent extends Vue {
    @Prop(Object)
    public stop: Stop;
}

Vue.component('Stop', StopComponent);
Vue.component('StopDetails', StopDetailsComponent);
Vue.component('StopMap', StopMapComponent);
