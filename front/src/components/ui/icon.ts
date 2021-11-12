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

import { Options, Vue } from "vue-class-component"
import { Prop } from 'vue-property-decorator'
import { IconDefinition, library } from "@fortawesome/fontawesome-svg-core"
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
} from "@fortawesome/pro-light-svg-icons";
import {
    faClock as faClockBold,
    faCodeCommit,
    faMinus,
    faPlus,
    faSpinnerThird,
    faClock,
    faLessThan,
} from "@fortawesome/pro-regular-svg-icons";
import {
    faExclamationTriangle as faSolidExclamationTriangle,
    faWalking,
    faClock as faSolidClock,
} from "@fortawesome/pro-solid-svg-icons";
import { FontAwesomeIcon, FontAwesomeLayers, FontAwesomeLayersText } from "@fortawesome/vue-fontawesome";
import WithRender from '@templates/ui/icon.html'
import { PropType } from "vue";
import { MessageType } from "@/model/message";
import { LineType } from "@/model";
import { faBus, faMetro, faTrain, faTram, faTrolleybus, faUnknown } from "@/icons";

type IconDescription = { icon: IconDefinition, [prop: string]: any }

type SimpleIcon = {
    type: 'simple',
} & IconDescription;

type StackedIcon = {
    type: 'stacked',
    icons: IconDescription[],
}

export type Icon = SimpleIcon | StackedIcon;

const simple = (icon: IconDefinition, props: any = {}): SimpleIcon => ({
    icon, ...props, type: "simple"
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

const definitions = {
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
    'stop': simple(faSign),
    'spinner': simple(faSpinnerThird, { spin: true }),
    'increment': simple(faPlus, { "fixed-width": true }),
    'decrement': simple(faMinus, { "fixed-width": true }),
    'relative-time': simple(faHourglassHalf),
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
    ...lineTypeIcons,
    ...messageTypeIcons,
};

export type PredefinedIcon = keyof typeof definitions;

const extractAllIcons = (icons: Icon[]) => icons.map(icon => {
    switch (icon.type) {
        case "simple":
            return [icon.icon];
        case "stacked":
            return icon.icons.map(stacked => stacked.icon);
    }
}).reduce((acc, cur) => [...acc, ...cur]);

library.add(...extractAllIcons(Object.values(definitions)));

@WithRender
@Options({
    name: "UiIcon",
    components: {
        fa: FontAwesomeIcon,
        faLayers: FontAwesomeLayers,
        faText: FontAwesomeLayersText,
    },
    props: {
        icon: {
            type: [String, Object] as PropType<PredefinedIcon | IconDefinition>,
            validator: (value: PredefinedIcon | IconDefinition) => typeof value === "object" || Object.keys(definitions).includes(value),
            required: true,
        }
    }
})
export class UiIcon extends Vue {
    @Prop()
    private icon: PredefinedIcon | IconDefinition;

    get definition(): Icon {
        return typeof this.icon === "string"
            ? definitions[this.icon] || definitions['unknown']
            : { icon: this.icon as IconDefinition, type: "simple" };
    }

    get attrs() {
        return { ...this.definition, ...this.$attrs };
    }

    get type() {
        return this.definition.type;
    }
}

export default UiIcon;
