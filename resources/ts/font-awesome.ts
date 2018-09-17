import Vue from 'vue'

import { library } from '@fortawesome/fontawesome-svg-core'

import { far } from "@fortawesome/pro-regular-svg-icons";
import { fas } from "@fortawesome/pro-solid-svg-icons";
import { fal } from "@fortawesome/pro-light-svg-icons";
import { fac } from "./icons";

import { FontAwesomeIcon, FontAwesomeLayers, FontAwesomeLayersText } from '@fortawesome/vue-fontawesome'

library.add(far, fas, fal, fac);

Vue.component('fa', FontAwesomeIcon);
Vue.component('fa-layers', FontAwesomeLayers);
Vue.component('fa-text', FontAwesomeLayersText);
