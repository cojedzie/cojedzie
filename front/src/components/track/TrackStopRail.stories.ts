import { Meta, StoryObj } from "@storybook/vue3-vite";
import TrackStopRail from "./TrackStopRail.vue";
import type { Track } from "@/model";
import { tracks } from "./track.fixtures";

type Args = { track: Track; current?: string };

export default {
    title: "Track/Stop Rail",
    component: TrackStopRail,
    argTypes: { track: { control: false } },
    args: { track: tracks.tram[0] },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render: Story["render"] = args => ({
    components: { TrackStopRail },
    setup: () => ({ args }),
    template: `
        <div style="inline-size: 390px">
            <track-stop-rail :track="args.track" :current="args.current" />
        </div>
    `,
});

/** Drawn in the tram's colour, the ends filled; the last stop takes no one anywhere */
export const Tram: Story = { render };

export const Bus: Story = { render, args: { track: tracks.bus[0] } };

/** A shortened run ends where the main track goes on */
export const SideTrack: Story = { render, args: { track: tracks.tram[2] } };

/** The stop whose timetable is open next to the rail */
export const OpenStop: Story = { render, args: { current: "dworzec" } };
