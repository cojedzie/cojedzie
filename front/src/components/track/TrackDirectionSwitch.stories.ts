import { Meta, StoryObj } from "@storybook/vue3-vite";
import { ref } from "vue";
import { userEvent, within } from "storybook/test";
import TrackDirectionSwitch from "./TrackDirectionSwitch.vue";
import type { Track } from "@/model";
import { tracks } from "./track.fixtures";

type Args = { tracks: Track[]; chosen: number; width: number };

export default {
    title: "Track/Direction Switch",
    component: TrackDirectionSwitch,
    argTypes: { tracks: { control: false }, chosen: { control: { type: "number" } } },
    args: { tracks: tracks.tram, chosen: 0, width: 390 },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render: Story["render"] = args => ({
    components: { TrackDirectionSwitch },
    setup: () => ({ args, track: ref(args.tracks[args.chosen]) }),
    template: `
        <div :style="{ inlineSize: args.width + 'px', display: 'flex' }">
            <track-direction-switch v-model="track" :tracks="args.tracks" />
        </div>
    `,
});

/** Two main tracks, by where they go; the shortened runs wait in the menu after them */
export const WithSideTracks: Story = { render };

/** A shortened run chosen: no direction is pressed, the menu holds the choice */
export const SideTrackChosen: Story = { render, args: { chosen: 2 } };

/** The menu opened: the other runs, from where to where, the chosen one marked */
export const MenuOpen: Story = {
    render,
    args: { chosen: 2 },
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole("button", { name: /^Inne przebiegi/ }));
    },
};

/** Only main tracks: no menu */
export const TwoMainTracks: Story = { render, args: { tracks: tracks.bus } };

/** On a narrow phone the directions shorten, the menu stays in the row */
export const Narrow: Story = { render, args: { width: 280 } };
