import { Meta, StoryObj } from "@storybook/vue3-vite";
import { ref } from "vue";
import TrackDirectionPicker from "./TrackDirectionPicker.vue";
import type { Track } from "@/model";
import { tracks } from "./track.fixtures";

type Args = { tracks: Track[] };

export default {
    title: "Track/Direction Picker",
    component: TrackDirectionPicker,
    argTypes: { tracks: { control: false } },
    args: { tracks: tracks.tram },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render: Story["render"] = args => ({
    components: { TrackDirectionPicker },
    setup: () => ({ args, track: ref(args.tracks[0]) }),
    template: `
        <div style="inline-size: 390px">
            <track-direction-picker v-model="track" :tracks="args.tracks" />
        </div>
    `,
});

/** The main tracks first, the shortened run after them with a quiet hint */
export const TramWithSideTrack: Story = { render };

export const Bus: Story = { render, args: { tracks: tracks.bus } };

/** Two tracks to the same destination get their first stop too */
export const SharedDestination: Story = {
    render,
    args: { tracks: [...tracks.tram, { ...tracks.tram[0], id: "t6-dworzec", stops: tracks.tram[0].stops?.slice(2) }] },
};
