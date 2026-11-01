import UiMenu from "./UiMenu.vue";
import UiMenuItem from "./UiMenuItem.vue";
import UiIcon from "./UiIcon.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";

export default {
    title: "Ui/Menu",
    component: UiMenu,
} satisfies Meta<typeof UiMenu>;

// the menu reaches the edges of the popup it is in, so the stories put it in one
export const Default: StoryObj<typeof UiMenu> = {
    render: () => ({
        components: { UiMenu, UiMenuItem, UiIcon },
        template: `
            <div class="ui-popup" style="width: 14rem">
                <ui-menu>
                    <ui-menu-item><ui-icon icon="timetable" fixed-width /> Tablica</ui-menu-item>
                    <ui-menu-item tag="a" href="#"><span class="grow">GitHub</span><ui-icon icon="github" /></ui-menu-item>
                </ui-menu>
            </div>
        `,
    }),
};

export const Overridden: StoryObj<typeof UiMenu> = {
    render: () => ({
        components: { UiMenu, UiMenuItem },
        template: `
            <div class="ui-popup" style="width: 14rem">
                <ui-menu>
                    <ui-menu-item style="flex-direction: column; align-items: stretch">
                        <span>Trójmiasto</span>
                        <span class="text-muted font-normal">2026-10-31 04:12</span>
                    </ui-menu-item>
                </ui-menu>
            </div>
        `,
    }),
};
