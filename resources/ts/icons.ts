import { IconPack, IconDefinition } from '@fortawesome/fontawesome-svg-core';

import bus        = require("../icons/light/bus.svg");
import tram       = require("../icons/light/tram.svg");
import trolleybus = require("../icons/light/trolleybus.svg");
import metro      = require("../icons/light/metro.svg");
import train      = require("../icons/light/train.svg");
import unknown    = require("../icons/light/unknown.svg");

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