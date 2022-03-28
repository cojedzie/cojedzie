<template>
    <slot name="button" :toggle="toggle">
        <button v-bind="$attrs" ref="button" type="button" @click="toggle">
            <ui-icon icon="info" fixed-width />
        </button>
    </slot>
    <teleport v-if="active" to="#popups">
        <ui-dialog :reference="button" offset="-12px, 20px" class="help" placement="right-start" mobile-behaviour="modal">
            <template #title>
                <slot name="title" />
            </template>
            <slot />
        </ui-dialog>
    </teleport>
</template>

<script lang="ts">
import { defineComponent, PropType, ref } from "vue";
import { supply } from "@/utils";
import { createMutex } from "@/composables/useMutex";

export type HelpTrigger = "click" | "long-hover";

const mutex = createMutex();

export const UiHelp = defineComponent({
  props: {
    trigger: {
      type: Array as PropType<HelpTrigger[]>,
      required: false,
      default: supply<HelpTrigger[]>(["click", "long-hover"])
    }
  },
  setup() {
    const button = ref<HTMLElement>(null);
    const { active, toggle } = mutex.use();

    return {
      active,
      toggle,
      button,
    }
  },
})

export default UiHelp;
</script>
