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

import { defineComponent } from 'vue';

import 'mapbox-gl-leaflet'
import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype['_getIconUrl'];

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

export const LVectorLayer = defineComponent({
    props: {
        url: {
            type: String,
            required: true,
        },
        attribution: {
            type: String,
            default: "",
        },
    },
    mounted(): void {
        // @ts-ignore
        this['mapObject'] = L.mapboxGL({
            style: this.url,
            attribution: this.attribution
        });

        this.$nextTick(() => {
            const map = this.$parent['leafletObject'];

            this['mapObject'].addTo(map);
        })
    },
    beforeUmount(): void {
        this.$parent['leafletObject'].removeLayer(this['mapObject'])
    },
    render: () => null,
});

export { LMap, LTileLayer, LMarker, LIcon, LControl, LPopup } from '@vue-leaflet/vue-leaflet';
