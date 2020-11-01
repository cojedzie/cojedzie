import { IconDefinition, IconPack } from '@fortawesome/fontawesome-svg-core';

import * as bus from "@resources/icons/light/bus.svg";
import * as tram from "@resources/icons/light/tram.svg";
import * as trolleybus from "@resources/icons/light/trolleybus.svg";
import * as metro from "@resources/icons/light/metro.svg";
import * as train from "@resources/icons/light/train.svg";
import * as unknown from "@resources/icons/light/unknown.svg";

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
