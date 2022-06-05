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

import { IconDefinition, IconPack, library } from '@fortawesome/fontawesome-svg-core';

import { MessageType } from "@/model/message";
import { LineType } from "@/model";

import bus from "@resources/icons/light/bus.svg";
import tram from "@resources/icons/light/tram.svg";
import trolleybus from "@resources/icons/light/trolleybus.svg";
import metro from "@resources/icons/light/metro.svg";
import train from "@resources/icons/light/train.svg";
import unknown from "@resources/icons/light/unknown.svg";

import {
    faBullhorn,
    faCheck,
    faCheckDouble,
    faChevronCircleUp,
    faChevronDown,
    faChevronUp,
    faCog,
    faExclamationTriangle,
    faHistory,
    faHourglassHalf,
    faInfoCircle,
    faMapMarkerAlt,
    faMoon,
    faQuestionCircle,
    faQuestionSquare,
    faSearch,
    faSign,
    faStar,
    faSync,
    faTimes,
    faTrashAlt,
    faBullseyePointer,
    faMapMarkedAlt,
} from "@fortawesome/pro-light-svg-icons";

import {
    faClock as faClockBold,
    faCodeCommit,
    faMinus,
    faPlus,
    faSpinnerThird,
    faClock,
    faLessThan,
    faArrowRight,
} from "@fortawesome/pro-regular-svg-icons";

import {
    faExclamationTriangle as faSolidExclamationTriangle,
    faWalking,
    faClock as faSolidClock,
} from "@fortawesome/pro-solid-svg-icons";

const faBus: IconDefinition = <any>{
    prefix:   'fac',
    iconName: 'bus',
    icon: [ 512, 512, [], null, bus]
};

const faTram = <any>{
    prefix:   'fac',
    iconName: 'tram',
    icon: [ 512, 512, [], null, tram]
};

const faTrain = <any>{
    prefix:   'fac',
    iconName: 'train',
    icon: [ 512, 512, [], null, train]
};

const faTrolleybus = <any>{
    prefix:   'fac',
    iconName: 'trolleybus',
    icon: [ 512, 512, [], null, trolleybus]
};

const faMetro = <any>{
    prefix:   'fac',
    iconName: 'metro',
    icon: [ 512, 512, [], null, metro]
};

const faUnknown = <any>{
    prefix:   'fac',
    iconName: 'unknown',
    icon: [ 512, 512, [], null, unknown]
};

const fac: IconPack = {
    faBus, faTram, faTrain, faTrolleybus, faMetro, faUnknown
};

interface IconDescription {
    icon: IconDefinition,
    [other: string]: unknown
}

type SimpleIcon = {
    type: 'simple',
} & IconDescription;

type StackedIcon = {
    type: 'stacked',
    icons: IconDescription[],
}

export type Icon = SimpleIcon | StackedIcon;

const simple = (icon: IconDefinition, props: Record<string, unknown> = {}): SimpleIcon => ({
    icon,
    ...props,
    type: "simple"
});

const stack = (icons: IconDescription[]): StackedIcon => ({ type: "stacked", icons });

const lineTypeIcons: Record<`line-${LineType}`, Icon> = Object
    .values({
        tram: faTram,
        train: faTrain,
        bus: faBus,
        trolleybus: faTrolleybus,
        metro: faMetro,
        other: faUnknown,
    })
    .map<[`line-${LineType}`, Icon]>(icon => [`line-${ icon.iconName as LineType }`, simple(icon)])
    .reduce((acc, [icon, definition]) => ({ ...acc, [icon]: definition }), {}) as Record<`line-${ LineType }`, Icon>

const messageTypeIcons: Record<`message-${MessageType}`, Icon> = {
    'message-breakdown': simple(faExclamationTriangle),
    'message-info': simple(faInfoCircle),
    'message-unknown': simple(faQuestionCircle),
};

export const icons = {
    'favourite': simple(faStar),
    'unknown': simple(faQuestionSquare),
    'add': simple(faCheck),
    'add-all': simple(faCheckDouble),
    'remove-stop': simple(faTimes),
    'delete': simple(faTrashAlt),
    'messages': simple(faBullhorn),
    'timetable': simple(faClock),
    'settings': simple(faCog),
    'refresh': simple(faSync),
    'chevron-down': simple(faChevronDown),
    'chevron-up': simple(faChevronUp),
    'search': simple(faSearch),
    'info': simple(faInfoCircle),
    'warning': simple(faExclamationTriangle),
    'night': simple(faMoon),
    'fast': simple(faWalking),
    'track': simple(faCodeCommit),
    'info-hide': simple(faChevronCircleUp),
    'map': simple(faMapMarkerAlt),
    'map-marked': simple(faMapMarkedAlt),
    'stop': simple(faSign),
    'spinner': simple(faSpinnerThird, { spin: true }),
    'increment': simple(faPlus, { "fixed-width": true }),
    'decrement': simple(faMinus, { "fixed-width": true }),
    'relative-time': simple(faHourglassHalf),
    'destination': simple(faArrowRight),
    'relative-time-limit': stack([
        { icon: faLessThan },
        { icon: faSolidClock, transform: "shrink-5 down-6 left-5" }
    ]),
    'departure-warning': stack([
        { icon: faClockBold },
        { icon: faSolidExclamationTriangle, transform: "shrink-5 down-4 right-6" }
    ]),
    'close': simple(faTimes),
    'history': simple(faHistory),
    'target': simple(faBullseyePointer),
    ...lineTypeIcons,
    ...messageTypeIcons,
};

export type PredefinedIcon = keyof typeof icons;

const extractAllIcons = (icons: Icon[]) => icons.map(icon => {
    switch (icon.type) {
        case "simple":
            return [icon.icon];
        case "stacked":
            return icon.icons.map(stacked => stacked.icon);
    }
}).reduce((acc, cur) => [...acc, ...cur]);

library.add(...extractAllIcons(Object.values(icons)));
