import { Message } from "@/model/message";
import moment from "moment";
import { Meta, Story, StoryFn } from "@storybook/vue3";
import MessagesMessage from "@/components/messages/MessagesMessage.vue";
import { Line, Stop } from "@/model";
import { computed } from "vue";

const lines: Line[] = [
    {
        symbol: "6",
        type: "tram",
        fast: false,
        night: false,
        operator: {
            id: "2",
            $type: "vnd.cojedzie.operator",
        },
        id: "6",
        $type: "vnd.cojedzie.line",
    },
    {
        symbol: "9",
        type: "tram",
        fast: false,
        night: false,
        operator: {
            id: "2",
            $type: "vnd.cojedzie.operator",
        },
        id: "9",
        $type: "vnd.cojedzie.line",
    },
    {
        symbol: "12",
        type: "tram",
        fast: false,
        night: false,
        operator: {
            id: "2",
            $type: "vnd.cojedzie.operator",
        },
        id: "12",
        $type: "vnd.cojedzie.line",
    },
];

const stops: Stop[] = [
    {
        name: "Przeróbka",
        description: null,
        variant: "01",
        location: {
            lng: 18.68343,
            lat: 54.35748,
        },
        onDemand: false,
        group: "Przeróbka",
        id: "14962",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Głęboka",
        description: null,
        variant: "01",
        location: {
            lng: 18.67149,
            lat: 54.35217,
        },
        onDemand: false,
        group: "Głęboka",
        id: "14960",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Brama Żuławska",
        description: null,
        variant: "02",
        location: {
            lng: 18.66933,
            lat: 54.34774,
        },
        onDemand: false,
        group: "Brama Żuławska",
        id: "2114",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Akademia Muzyczna",
        description: null,
        variant: "01",
        location: {
            lng: 18.66424,
            lat: 54.34463,
        },
        onDemand: false,
        group: "Akademia Muzyczna",
        id: "2110",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Chmielna",
        description: null,
        variant: "01",
        location: {
            lng: 18.6571,
            lat: 54.34547,
        },
        onDemand: false,
        group: "Chmielna",
        id: "2108",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Żabi Kruk",
        description: null,
        variant: "02",
        location: {
            lng: 18.65164,
            lat: 54.3466,
        },
        onDemand: false,
        group: "Żabi Kruk",
        id: "2106",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Okopowa",
        description: null,
        variant: "01",
        location: {
            lng: 18.64633,
            lat: 54.34758,
        },
        onDemand: false,
        group: "Okopowa",
        id: "2104",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Brama Wyżynna",
        description: null,
        variant: "01",
        location: {
            lng: 18.64618,
            lat: 54.35049,
        },
        onDemand: false,
        group: "Brama Wyżynna",
        id: "2102",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Dworzec Główny",
        description: null,
        variant: "01",
        location: {
            lng: 18.64564,
            lat: 54.35539,
        },
        onDemand: false,
        group: "Dworzec Główny",
        id: "2001",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Brama Oliwska",
        description: null,
        variant: "01",
        location: {
            lng: 18.64159,
            lat: 54.36212,
        },
        onDemand: false,
        group: "Brama Oliwska",
        id: "2005",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Chodowieckiego",
        description: null,
        variant: "01",
        location: {
            lng: 18.63837,
            lat: 54.36404,
        },
        onDemand: false,
        group: "Chodowieckiego",
        id: "2007",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Uniwersytet Medyczny",
        description: null,
        variant: "01",
        location: {
            lng: 18.63357,
            lat: 54.3669,
        },
        onDemand: false,
        group: "Uniwersytet Medyczny",
        id: "2009",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Traugutta",
        description: null,
        variant: "01",
        location: {
            lng: 18.62962,
            lat: 54.36923,
        },
        onDemand: false,
        group: "Traugutta",
        id: "2011",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Opera Bałtycka",
        description: null,
        variant: "01",
        location: {
            lng: 18.62468,
            lat: 54.37214,
        },
        onDemand: false,
        group: "Opera Bałtycka",
        id: "2015",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Politechnika",
        description: null,
        variant: "01",
        location: {
            lng: 18.62147,
            lat: 54.37404,
        },
        onDemand: false,
        group: "Politechnika",
        id: "2017",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Miszewskiego",
        description: null,
        variant: "01",
        location: {
            lng: 18.61515,
            lat: 54.37585,
        },
        onDemand: false,
        group: "Miszewskiego",
        id: "2019",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Jaśkowa Dolina",
        description: null,
        variant: "01",
        location: {
            lng: 18.60642,
            lat: 54.37843,
        },
        onDemand: false,
        group: "Jaśkowa Dolina",
        id: "2021",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Klonowa",
        description: null,
        variant: "01",
        location: {
            lng: 18.60297,
            lat: 54.37997,
        },
        onDemand: false,
        group: "Klonowa",
        id: "2023",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Galeria Bałtycka",
        description: null,
        variant: "01",
        location: {
            lng: 18.59857,
            lat: 54.38233,
        },
        onDemand: false,
        group: "Galeria Bałtycka",
        id: "2025",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Wojska Polskiego",
        description: null,
        variant: "01",
        location: {
            lng: 18.59055,
            lat: 54.38707,
        },
        onDemand: false,
        group: "Wojska Polskiego",
        id: "2027",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Zamenhofa",
        description: null,
        variant: "01",
        location: {
            lng: 18.58489,
            lat: 54.38691,
        },
        onDemand: false,
        group: "Zamenhofa",
        id: "2029",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Zajezdnia",
        description: null,
        variant: "01",
        location: {
            lng: 18.58156,
            lat: 54.38848,
        },
        onDemand: false,
        group: "Zajezdnia",
        id: "2031",
        $type: "vnd.cojedzie.stop",
    },
    {
        name: "Strzyża PKM",
        description: null,
        variant: "03",
        location: {
            lng: 18.57821,
            lat: 54.39117,
        },
        onDemand: false,
        group: "Strzyża PKM",
        id: "213",
        $type: "vnd.cojedzie.stop",
    },
];

const message: Message = {
    type: "info",
    $type: "vnd.cojedzie.message",
    id: "example",
    message:
        "Awaria tramwaju linii nr 9 w kierunku Strzyża SKM przy przystanku Opera Bałtycka, opóźnienia na liniach: 6, 9, 12.",
    validFrom: moment().subtract(1, "day"),
    validTo: moment().subtract(-1, "hour"),
    $refs: {
        lines: {
            items: lines,
            total: lines.length,
            count: lines.length,
        },
        stops: {
            items: stops,
            total: stops.length,
            count: stops.length,
        },
    },
};

export default {
    component: MessagesMessage,
    title: "Messages/Message",
    argTypes: {
        type: {
            options: ["info", "breakdown", "unknown"],
            control: "select",
        },
        showLines: {
            control: "boolean",
        },
        lines: {
            if: { arg: "showLines" },
        },
        showStops: {
            control: "boolean",
        },
        stops: {
            if: { arg: "showStops" },
        },
        showValidity: {
            control: "boolean",
        },
        validFrom: {
            control: "date",
            if: { arg: "showValidity" },
        },
        validTo: {
            control: "date",
            if: { arg: "showValidity" },
        },
    },
    args: {
        type: "info",
        showLines: true,
        lines: message.$refs.lines,
        showStops: true,
        stops: message.$refs.stops,
        showValidity: true,
        validFrom: message.validFrom,
        validTo: message.validTo,
    },
} as Meta<typeof MessagesMessage>;

export const Primary: StoryFn = args => ({
    components: { MessagesMessage },
    setup() {
        return {
            message,
            args,
            validFrom: computed(() => moment(args.validFrom)),
            validTo: computed(() => moment(args.validTo)),
        };
    },
    template: `
        <messages-message :type="args.type"
                          :lines="args.showLines ? args.lines.items : []"
                          :stops="args.showStops ? args.stops.items : []"
                          :valid-from="args.showValidity ? validFrom : undefined"
                          :valid-to="args.showValidity ? validTo : undefined"
                          :message="message.message"/>
    `,
});
