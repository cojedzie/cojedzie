import UiIcon from './UiIcon.vue';
import { icons } from '@/icons';
import { Meta, StoryObj } from "@storybook/vue3";

const Template = (args) => ({
  components: { UiIcon },
  setup: () => {
    return { args };
  },
  template: "<ui-icon :icon='args.icon'/>",
});

export default {
  title: 'Ui/Icon',
  component: UiIcon,

  argTypes: {
    icon: {
      defaultValue: 'unknown',
      options: Object.keys(icons),

      control: {
        type: 'select',
      },
    },
  },
} satisfies Meta<typeof UiIcon>;

export const Default: StoryObj<typeof UiIcon> = {
  render: Template.bind({}),
};
