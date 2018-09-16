import { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
import Vue from 'vue';

import * as L from 'leaflet'
import 'leaflet/dist/leaflet.css'

import * as iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import * as iconUrl from 'leaflet/dist/images/marker-icon.png';
import * as shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

Vue.component('LMap', LMap);
Vue.component('LTileLayer', LTileLayer);
Vue.component('LMarker', LMarker);

export { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
