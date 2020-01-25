import { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
import Vue from 'vue';

import * as L from 'leaflet'
import 'mapbox-gl-leaflet'
import 'leaflet/dist/leaflet.css'

import * as iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import * as iconUrl from 'leaflet/dist/images/marker-icon.png';
import * as shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype['_getIconUrl'];

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

export const LVectorLayer = Vue.extend({
    props: {
        url: {
            type: String,
            required: true,
        },
        token: {
            type: String,
            default: ""
        },
        attribution: String,
    },
    mounted(): void {
        this['mapObject'] = L.mapboxGL({
            style: this.url,
            accessToken: this.token,
            attribution: this.attribution
        } as any);

        this.$nextTick(() => {
            const map = this.$parent['mapObject'];

            this['mapObject'].addTo(map);
        })
    },
    beforeDestroy(): void {
        this.$parent['mapObject'].removeLayer(this['mapObject'])
    },
    render: () => null,
});

Vue.component('LMap', LMap);
Vue.component('LTileLayer', LTileLayer);
Vue.component('LVectorLayer', LVectorLayer);
Vue.component('LMarker', LMarker);

export { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
