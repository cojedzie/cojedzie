<template>
  <slot name="button" :toggle="toggle">
    <button type="button" @click="toggle" v-bind="$attrs" ref="button">
      <ui-icon icon="info" fixed-width/>
    </button>
  </slot>
  <teleport to="#popups" v-if="active">
    <ui-dialog :reference="button" offset="-12px, 20px" class="help" placement="right-start" mobile-behaviour="modal">
      <template #title>
        <slot name="title"/>
      </template>
      <slot/>
    </ui-dialog>
  </teleport>
</template>

<script lang="ts">
import { defineComponent, PropType, ref } from "vue";
import { supply } from "@/utils";
import { createMutex } from "@/utils/mutex";

export type HelpTrigger = "click" | "long-hover";

const hoverTimeout = 1000;

const mutex = createMutex();

export const UiHelp = defineComponent({
  props: {
    trigger: {
      type: Array as PropType<HelpTrigger[]>,
      required: false,
      default: supply<HelpTrigger[]>(["click", "long-hover"])
    }
  },
  setup({ trigger }) {
    const button = ref<HTMLElement>(null);
    const { active, toggle } = mutex.use();

    return {
      trigger,
      active,
      toggle,
      button,
    }
  },
})

export default UiHelp;
</script>
