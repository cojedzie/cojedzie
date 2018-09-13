import { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
import Vue from 'vue';

import L = require('leaflet')
import 'leaflet/dist/leaflet.css'

import iconRetinaUrl = require('leaflet/dist/images/marker-icon-2x.png');
import iconUrl       = require('leaflet/dist/images/marker-icon.png');
import shadowUrl     = require('leaflet/dist/images/marker-shadow.png');

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

Vue.component('LMap', LMap);
Vue.component('LTileLayer', LTileLayer);
Vue.component('LMarker', LMarker);

export { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
