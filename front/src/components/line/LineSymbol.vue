<template>
    <span class="line__symbol flex" :class="{ [`line--${line.type}`]: true, 'line--night': line.night, 'line--fast': line.fast }">
        <span class="flex align-items-stretch">
            <slot v-if="!simple" name="icon">
                <span class="icon">
                    <ui-icon :icon="`line-${line.type}`" fixed-width />
                </span>
            </slot>
            <slot name="badge">
                <span class="badge badge-dark flex">
                    <ui-icon v-if="line.night && !simple" icon="night" fixed-width />
                    {{ line.symbol }}
                    <ui-icon v-if="line.fast" icon="fast" />
                </span>
            </slot>
        </span>
    </span>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop } from "vue-property-decorator";
import { Line } from "@/model";

@Options({ name: "LineSymbol" })
export default class LineSymbol extends Vue {
    @Prop(Object)
    public line: Line;

    @Prop(Boolean)
    public simple: boolean;
}
</script>
