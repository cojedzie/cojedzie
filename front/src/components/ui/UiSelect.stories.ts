import UiSelect from './UiSelect.vue';
import { ref } from 'vue';

const Template = (args) => ({
  components: { UiSelect },
  setup: () => {
    const value = ref(args.value);
    return { value, args };
  },
  template:
    "<ui-select v-model='value' :options='args.options' v-slot='{ option }' allow-empty>T: {{ option }}</ui-select>",
});

export default {
  title: 'UI/Select',
  component: UiSelect,

  args: {
    value: null,
  },

  argTypes: {
    value: {
      type: 'object',
    },
  },
};

export const Default = {
  render: Template.bind({}),
  args: {
    options: ['A', 'B', 'C'],
  },
};
