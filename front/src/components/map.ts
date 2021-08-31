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

import { LControl, LIcon, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { defineComponent } from 'vue';

import 'mapbox-gl-leaflet'
import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import * as iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import * as iconUrl from 'leaflet/dist/images/marker-icon.png';
import * as shadowUrl from 'leaflet/dist/images/marker-shadow.png';
import { app } from "@/components/application";

delete L.Icon.Default.prototype['_getIconUrl'];

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

export const LVectorLayer = defineComponent({
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
        // @ts-ignore
        this['mapObject'] = L.mapboxGL({
            style: this.url,
            accessToken: this.token,
            attribution: this.attribution
        } as any);

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
