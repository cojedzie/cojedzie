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

import { h } from "vue";
import type { Component, FunctionalComponent } from "vue";
import {
    IconAlertTriangle,
    IconArrowRight,
    IconBusStop,
    IconCheck,
    IconChecks,
    IconChevronDown,
    IconChevronUp,
    IconClock,
    IconClockExclamation,
    IconClockFilled,
    IconCurrentLocation,
    IconGitCommit,
    IconHelpCircle,
    IconHelpSquareRounded,
    IconHistory,
    IconHourglass,
    IconInfoCircle,
    IconLoader2,
    IconMapPin,
    IconMapPinFilled,
    IconMathLower,
    IconMinus,
    IconMoon,
    IconPlus,
    IconRefresh,
    IconSearch,
    IconSettings,
    IconSpeakerphone,
    IconStar,
    IconTrash,
    IconWalk,
    IconX,
} from "@tabler/icons-vue";

import { MessageType } from "@/model/message";
import { LineType } from "@/model";

import bus from "@resources/icons/light/bus.svg";
import tram from "@resources/icons/light/tram.svg";
import trolleybus from "@resources/icons/light/trolleybus.svg";
import metro from "@resources/icons/light/metro.svg";
import train from "@resources/icons/light/train.svg";
import unknown from "@resources/icons/light/unknown.svg";

// our own icons are single filled paths on a 512x512 canvas, see svg-icon-loader
const custom =
    (path: string): FunctionalComponent =>
    () =>
        h("svg", { xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 512 512", fill: "currentColor" }, [
            h("path", { d: path }),
        ]);

export interface IconDescription {
    icon: Component;
    spin?: boolean;
    fixedWidth?: boolean;
}

export interface IconLayer {
    icon: Component;
    // size relative to the whole icon
    scale?: number;
    // offset from the centre, in em
    x?: number;
    y?: number;
}

type SimpleIcon = {
    type: "simple";
} & IconDescription;

type StackedIcon = {
    type: "stacked";
    icons: IconLayer[];
};

export type Icon = SimpleIcon | StackedIcon;

const simple = (icon: Component, props: Omit<IconDescription, "icon"> = {}): SimpleIcon => ({
    icon,
    ...props,
    type: "simple",
});

const stack = (icons: IconLayer[]): StackedIcon => ({
    type: "stacked",
    icons,
});

// there is no metro line type yet, but the icon is already drawn
const lineTypeIcons: Record<`line-${LineType | "metro"}`, Icon> = {
    "line-tram": simple(custom(tram)),
    "line-train": simple(custom(train)),
    "line-bus": simple(custom(bus)),
    "line-trolleybus": simple(custom(trolleybus)),
    "line-metro": simple(custom(metro)),
    "line-other": simple(custom(unknown)),
    "line-unknown": simple(custom(unknown)),
};

const messageTypeIcons: Record<`message-${MessageType}`, Icon> = {
    "message-breakdown": simple(IconAlertTriangle),
    "message-info": simple(IconInfoCircle),
    "message-unknown": simple(IconHelpCircle),
};

export const icons = {
    favourite: simple(IconStar),
    unknown: simple(IconHelpSquareRounded),
    add: simple(IconCheck),
    "add-all": simple(IconChecks),
    "remove-stop": simple(IconX),
    delete: simple(IconTrash),
    clear: simple(IconX),
    messages: simple(IconSpeakerphone),
    timetable: simple(IconClock),
    settings: simple(IconSettings),
    refresh: simple(IconRefresh),
    "chevron-down": simple(IconChevronDown),
    "chevron-up": simple(IconChevronUp),
    search: simple(IconSearch),
    info: simple(IconInfoCircle),
    warning: simple(IconAlertTriangle),
    night: simple(IconMoon),
    fast: simple(IconWalk),
    track: simple(IconGitCommit),
    map: simple(IconMapPin),
    "map-marked": simple(IconMapPin),
    "map-marked:selected": simple(IconMapPinFilled),
    stop: simple(IconBusStop),
    spinner: simple(IconLoader2, { spin: true }),
    increment: simple(IconPlus, { fixedWidth: true }),
    decrement: simple(IconMinus, { fixedWidth: true }),
    "relative-time": simple(IconHourglass),
    destination: simple(IconArrowRight),
    "relative-time-limit": stack([
        { icon: IconMathLower, scale: 0.7, x: 0.18, y: -0.15 },
        { icon: IconClockFilled, scale: 0.5, x: -0.25, y: 0.25 },
    ]),
    "departure-warning": simple(IconClockExclamation),
    close: simple(IconX),
    history: simple(IconHistory),
    target: simple(IconCurrentLocation),
    "ui-select:open": simple(IconChevronDown),
    ...lineTypeIcons,
    ...messageTypeIcons,
};

export type PredefinedIcon = keyof typeof icons;
