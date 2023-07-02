import TrackPicker from "./TrackPicker.vue";
import useDataFromEndpoint from "@/composables/useDataFromEndpoint";
import { ref } from "vue";

export default {
    title: "Track/Track Picker",
    component: TrackPicker,
    argTypes: {
        query: {
            control: "object",
        },
    },
};

const Template = args => ({
    components: { TrackPicker },
    setup: () => {
        const { data: tracks } = useDataFromEndpoint("v1_track_list", {
            version: "1.0",
            params: { provider: "trojmiasto" },
            query: args.query,
        });

        const value = ref();

        return { args, value, tracks: tracks || [] };
    },
    template: '<track-picker :tracks="tracks" v-model="value" style="width: 600px"/>',
});

export const Primary = Template.bind({});
Primary.args = {
    query: {
        embed: "stops",
        line: "6",
    },
};
