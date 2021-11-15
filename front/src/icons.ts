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

import { IconDefinition, IconPack } from '@fortawesome/fontawesome-svg-core';

import bus from "@resources/icons/light/bus.svg";
import tram from "@resources/icons/light/tram.svg";
import trolleybus from "@resources/icons/light/trolleybus.svg";
import metro from "@resources/icons/light/metro.svg";
import train from "@resources/icons/light/train.svg";
import unknown from "@resources/icons/light/unknown.svg";

export const faBus: IconDefinition = <any>{
    prefix:   'fac',
    iconName: 'bus',
    icon: [ 512, 512, [], null, bus]
};

export const faTram = <any>{
    prefix:   'fac',
    iconName: 'tram',
    icon: [ 512, 512, [], null, tram]
};

export const faTrain = <any>{
    prefix:   'fac',
    iconName: 'train',
    icon: [ 512, 512, [], null, train]
};

export const faTrolleybus = <any>{
    prefix:   'fac',
    iconName: 'trolleybus',
    icon: [ 512, 512, [], null, trolleybus]
};

export const faMetro = <any>{
    prefix:   'fac',
    iconName: 'metro',
    icon: [ 512, 512, [], null, metro]
};

export const faUnknown = <any>{
    prefix:   'fac',
    iconName: 'unknown',
    icon: [ 512, 512, [], null, unknown]
};

export const fac: IconPack = {
    faBus, faTram, faTrain, faTrolleybus, faMetro, faUnknown
};
