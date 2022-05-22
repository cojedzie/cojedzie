<template>
    <keep-alive>
        <slot v-if="visible"></slot>
    </keep-alive>
</template>

<script lang="ts">
import { Prop, Watch } from "vue-property-decorator";
import { Options, Vue } from "vue-class-component";

@Options({ name: "Lazy" })
export class Lazy extends Vue {
    @Prop(Boolean)
    public    activate: boolean;
    protected visible:  boolean = false;

    @Watch('activate')
    private onVisibilityChange(value, old) {
        this.visible = value || old;
    }
}

export default Lazy;
</script>
