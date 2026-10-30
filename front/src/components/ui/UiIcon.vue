<template>
    <fa v-if="definition.type === 'simple'" v-bind="{ ...definition, ...$attrs }" class="ui-icon" />
    <fa-layers v-else-if="definition.type === 'stacked'" class="ui-icon">
        <fa v-for="(entry, index) in definition.icons" v-bind="entry" :key="index" :icon="entry.icon" />
    </fa-layers>
</template>

<script lang="ts">
import { computed, defineComponent, PropType } from "vue";
import { Icon, icons, PredefinedIcon } from "@/icons";
import { IconDefinition } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon, FontAwesomeLayers } from "@fortawesome/vue-fontawesome";

export default defineComponent({
    name: "UiIcon",
    components: {
        fa: FontAwesomeIcon,
        faLayers: FontAwesomeLayers,
    },
    props: {
        icon: {
            type: [String, Object] as PropType<PredefinedIcon | IconDefinition>,
            validator: (value: PredefinedIcon | IconDefinition) =>
                typeof value === "object" || Object.keys(icons).includes(value),
            required: true,
        },
    },
    setup(props) {
        const definition = computed<Icon>(() =>
            typeof props.icon === "string"
                ? icons[props.icon] || icons["unknown"]
                : { icon: props.icon as IconDefinition, type: "simple" },
        );

        return { definition };
    },
});
</script>
