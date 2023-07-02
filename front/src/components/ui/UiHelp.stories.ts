import UiHelp from "./UiHelp.vue";
import { StoryObj } from "@storybook/vue3";
import { createMutex } from "@/composables/useMutex";

import figure from "@resources/images/help/departures-relative-time.png";

export default {
    title: "Ui/Help",
    component: UiHelp,

    argTypes: {
        title: {
            control: {
                type: "text",
            },
        },

        caption: {
            defaultValue: "Some caption for image",

            control: {
                type: "text",
            },
        },

        icon: {
            options: [null, "unknown", "info", "stop"],
            description: "Just for example, icon can be placed as arbitrary component into the title slot.",

            control: {
                type: "select",

                labels: {
                    "": "None",
                },
            },
        },
    },
};

const createRenderFunction = template => args => ({
    components: { UiHelp },
    setup: () => {
        const mutex = createMutex();
        return { args, mutex };
    },
    template,
});

export const Basic: StoryObj<any> = {
    render: createRenderFunction(`
      <ui-help :mutex='mutex'>
        <template #title><ui-icon v-if="args.icon" :icon="args.icon"/> {{ args.title }}</template>
        <p>{{ args.content }}</p>
      </ui-help>
    `),
    args: {
        title: "Example title",
        content: "Some kind of content",
    },
};

export const WithCoverPhoto = {
    render: createRenderFunction(`
      <ui-help v-bind='args' :mutex='mutex'>
        <template #title><ui-icon v-if="args.icon" :icon="args.icon"/> {{ args.title }}</template>
        <figure class="help__figure">
          <img src="${figure}" alt="" class="help__image"/>
          <figcaption>{{ args.caption }}</figcaption>
        </figure>
        <p>{{ args.content }}</p>
      </ui-help>
    `),
    args: {
        title: "Example title",
        content: "Some kind of content",
    },
};

export const WithCustomButton = {
    render: createRenderFunction(`
      <ui-help v-bind='args' :mutex='mutex'>
        <template #title><ui-icon v-if="args.icon" :icon="args.icon"/> {{ args.title }}</template>
        <template #button="{ toggle, ref }">
            <button class="btn btn-primary btn-sm" :ref="ref" @click="toggle">Show help</button>
        </template>
        <p>{{ args.content }}</p>
      </ui-help>
    `),
    args: {
        title: "Example title",
        content: "Some kind of content",
    },
};
