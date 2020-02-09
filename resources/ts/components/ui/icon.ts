import Vue from 'vue'
import { Component, Prop } from 'vue-property-decorator'
import { IconDefinition, library } from "@fortawesome/fontawesome-svg-core"
import { Dictionary } from "../../utils";
import {
    faBullhorn,
    faCheck,
    faCheckDouble,
    faChevronCircleUp,
    faChevronDown,
    faChevronUp,
    faClock,
    faCog,
    faExclamationTriangle,
    faInfoCircle,
    faMapMarkerAlt,
    faMoon,
    faQuestionCircle,
    faSearch,
    faSign,
    faStar,
    faSync,
    faTimes,
    faTrashAlt
} from "@fortawesome/pro-light-svg-icons";
import { faClock as faClockBold, faCodeCommit, faSpinnerThird } from "@fortawesome/pro-regular-svg-icons";
import { faExclamationTriangle as faSolidExclamationTriangle, faWalking } from "@fortawesome/pro-solid-svg-icons";
import { fac } from "../../icons";
import { FontAwesomeIcon, FontAwesomeLayers, FontAwesomeLayersText } from "@fortawesome/vue-fontawesome";

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

const stack = (icons: IconDescription[]): StackedIcon => ({type: "stacked", icons});

const lineTypeIcons = Object
    .values(fac)
    .map<[string, Icon]>(icon => [ `line-${icon.iconName}`, simple(icon) ])
    .reduce((acc, [icon, definition]) => ({ ...acc, [icon]: definition}), {})

const messageTypeIcons: Dictionary<Icon> = {
    'message-breakdown': simple(faExclamationTriangle),
    'message-info': simple(faInfoCircle),
    'message-unknown': simple(faQuestionCircle),
};

const definitions: Dictionary<Icon> = {
    'favourite': simple(faStar),
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
    'departure-warning': stack([
        {icon: faClockBold},
        {icon: faSolidExclamationTriangle, transform: "shrink-5 down-4 right-6"}
    ]),
    ...lineTypeIcons,
    ...messageTypeIcons,
};

const extractAllIcons = (icons: Icon[]) => icons.map(icon => {
    switch (icon.type) {
        case "simple":
            return [icon.icon];
        case "stacked":
            return icon.icons.map(stacked => stacked.icon);
    }
}).reduce((acc, cur) => [...acc, ...cur]);

library.add(...extractAllIcons(Object.values(definitions)));

@Component({
    template: require('../../../components/ui/icon.html'),
    components: {
        fa: FontAwesomeIcon,
        faLayers: FontAwesomeLayers,
        faText: FontAwesomeLayersText,
    }
})
export class UiIcon extends Vue {
    @Prop({
        type: [ String, Object ],
        validator: value => typeof value === "object" || Object.keys(definitions).includes(value),
        required: true,
    })
    icon: keyof typeof definitions;

    get definition() {
        return {...(typeof this.icon === "string" ? definitions[this.icon] : { icon: this.icon }), ...this.$attrs};
    }

    get type() {
        return definitions[this.icon].type;
    }
}

Vue.component('UiIcon', UiIcon);
