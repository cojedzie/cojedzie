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

import { App } from "vue";
import { SettingsDepartures, SettingsMessages } from "@/components/settings";
import UiDialog from "@/components/ui/dialog";
import { UiIcon, UiNumericInput, UiSwitch } from "@/components/ui";
import { DepartureComponent, DeparturesComponent } from "@/components/departures";
import { FavouritesAdderComponent, FavouritesComponent } from "@/components/favourites";
import { LControl, LIcon, LMap, LMarker, LPopup, LTileLayer, LVectorLayer } from "@/components/map";
import { MessagesComponent } from "@/components/messages";
import { FinderComponent, PickerStopComponent } from "@/components/picker";
import { StopComponent, StopDetailsComponent, StopMapComponent } from "@/components/stop";
import { LineComponent } from "@/components/line";
import { TooltipComponent } from "@/components/tooltip";
import { TripComponent } from "@/components/trip";
import { FoldComponent, LazyComponent } from "@/components/utils";

export * from "./application"
export * from './tooltip';
export * from './utils'
export * from './line'
export * from './picker'
export * from './departures'
export * from './stop'
export * from './messages'
export * from './map'
export * from './main'
export * from './favourites'
export * from './trip'
export * from './ui'
export * from './settings'
export * from "./provider-chooser"

export { Departures } from "../store";
export { Messages } from "../store";

export default function install(app: App) {
    app.component('SettingsDepartures', SettingsDepartures);
    app.component('SettingsMessages', SettingsMessages);

    app.component('UiDialog', UiDialog);
    app.component('UiIcon', UiIcon);
    app.component('UiNumericInput', UiNumericInput);
    app.component('UiSwitch', UiSwitch);

    app.component('Departures', DeparturesComponent);
    app.component('Departure', DepartureComponent);

    app.component('Favourites', FavouritesComponent);
    app.component('FavouritesAdder', FavouritesAdderComponent);

    app.component('LMap', LMap);
    app.component('LTileLayer', LTileLayer);
    app.component('LVectorLayer', LVectorLayer);
    app.component('LMarker', LMarker);
    app.component('LControl', LControl);
    app.component('LPopup', LPopup)
    app.component('LIcon', LIcon);

    app.component('Messages', MessagesComponent);

    app.component('StopFinder', FinderComponent);
    app.component('PickerStop', PickerStopComponent);

    app.component('AppStop', StopComponent);
    app.component('StopDetails', StopDetailsComponent);
    app.component('StopMap', StopMapComponent);
    app.component('LineSymbol', LineComponent);
    app.component('Tooltip', TooltipComponent);

    app.component('Trip', TripComponent);

    app.component('Fold', FoldComponent);
    app.component('Lazy', LazyComponent);

    // https://github.com/vuejs/vue/issues/7829
    app.component('Empty', {
        functional: true,
        render: (h, { data }) => h('template', data, '')
    });
}
