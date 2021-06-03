/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { LControl, LIcon, LMap, LMarker, LPopup, LTileLayer } from 'vue2-leaflet';
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
Vue.component('LControl', LControl);
Vue.component('LPopup', LPopup)
Vue.component('LIcon', LIcon);

export { LMap, LTileLayer, LMarker, LIcon, LControl, LPopup } from 'vue2-leaflet';
