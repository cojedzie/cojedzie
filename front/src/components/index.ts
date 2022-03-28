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

import { App, defineAsyncComponent, h } from "vue";
import { SettingsDepartures, SettingsMessages } from "@/components/settings";
import { UiFold, UiTooltip, UiIcon, UiNumericInput, UiSwitch, UiDialog, UiHelp } from "@/components/ui";
import Lazy from "@/components/utils";
import DeparturesList from "@/components/departures/list";
import FavouritesList from "@/components/favourites/list";
import FavouritesAdder from "@/components/favourites/adder";
import StopPicker from "@/components/stop-picker/picker";
import StopLabel from "@/components/stop/label";
import { StopDetailsDialog } from "@/components/stop";
import StopMap from "@/components/stop/map";
import MessagesList from "@/components/messages/list";
import LineSymbol from "@/components/line/symbol";
import TripSchedule from "@/components/trip/schedule";

export * from "./application"
export * from './utils'
export * from './map'
export * from './main'
export * from './ui'
export * from './settings'
export * from "./provider-chooser"
export * from "./departures"
export * from "./favourites"
export * from "./line"
export * from './messages'
export * from './stop'
export * from './stop-picker'

export default function install(Vue: App) {
    Vue.component('SettingsDepartures', SettingsDepartures);
    Vue.component('SettingsMessages', SettingsMessages);

    Vue.component('UiDialog', UiDialog);
    Vue.component('UiIcon', UiIcon);
    Vue.component('UiNumericInput', UiNumericInput);
    Vue.component('UiSwitch', UiSwitch);
    Vue.component('UiFold', UiFold);
    Vue.component('UiTooltip', UiTooltip);
    Vue.component('UiHelp', UiHelp);

    Vue.component('DeparturesList', DeparturesList);

    Vue.component('FavouritesList', FavouritesList);
    Vue.component('FavouritesAdder', FavouritesAdder);

    Vue.component('UiMap', defineAsyncComponent(() => import("@/components/ui/UiMap.vue")))

    Vue.component('LMarker', defineAsyncComponent(() => import("@/components/map").then(module => module.LMarker)));
    Vue.component('LControl', defineAsyncComponent(() => import("@/components/map").then(module => module.LControl)));
    Vue.component('LPopup', defineAsyncComponent(() => import("@/components/map").then(module => module.LPopup)));
    Vue.component('LIcon', defineAsyncComponent(() => import("@/components/map").then(module => module.LIcon)));

    Vue.component('MessagesList', MessagesList);

    Vue.component('StopPicker', StopPicker);
    Vue.component('StopLabel', StopLabel);
    Vue.component('StopMap', StopMap);

    Vue.component('LineSymbol', LineSymbol);

    Vue.component('TripSchedule', TripSchedule);

    // eslint-disable-next-line vue/multi-word-component-names
    Vue.component('Lazy', Lazy);

    // https://github.com/vuejs/vue/issues/7829
    // eslint-disable-next-line vue/multi-word-component-names
    Vue.component('Empty', (props, context) => h('template', context.attrs, ''));
}
