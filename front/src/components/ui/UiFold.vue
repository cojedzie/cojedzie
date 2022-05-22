<template>
    <div
        class="fold"
        :class="[`fold--${state}`]"
        :aria-expanded="visible ? 'true' : 'false'"
        @transitionend="handleTransitionEnd"
    >
        <div ref="inner" class="fold__inner" :tabindex="visible ? null : -1">
            <lazy v-if="lazy" :activate="visible">
                <slot />
            </lazy>
            <slot v-else />
        </div>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import { Prop, Watch } from "vue-property-decorator";

export type UiFoldState = "folded" | "expanding" | "expanded" | "folding";

@Options({ name: "UiFold" })
export default class UiFold extends Vue {
    @Prop(Boolean)
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    public state: UiFoldState;

    data() {
        return {
            state: this.visible ? "expanded" : "folded"
        }
    }

    @Watch('visible')
    private resize() {
        if (this.visible) {
            this.expand();
        } else {
            this.fold();
        }
    }

    private handleTransitionEnd(event: TransitionEvent) {
        if (event.target !== this.$el) {
            // it's not about us
            return;
        }

        const root = this.$el as HTMLDivElement;

        switch (this.state) {
            case "expanded":
            case "expanding":
                root.style.height = "auto";
                this.state = "expanded";
                break;
            case "folded":
            case "folding":
                this.state = "folded";
        }
    }

    private fold() {
        const root = this.$el as HTMLDivElement;
        const state = this.state;

        switch (state) {
            case "folded":
            case "folding":
                // no-op, it's already folded
                break;

            case "expanded":
                root.style.height = `${ root.clientHeight }px`;

                // DOM must be updated and style must be applied in order for this to work
                setTimeout(() => {
                    root.style.height = '0px';
                    this.state = "folding";
                })
                break;

            case "expanding":
                // reverse process
                root.style.height = '0px';
                this.state = "folding";
                break;
        }
    }

    private expand() {
        const root = this.$el as HTMLDivElement;
        const inner = this.$refs['inner'] as HTMLDivElement;
        const state = this.state;

        switch (state) {
            case "expanded":
            case "expanding":
                // no-op, it's already folded
                break;

            case "folded":
            case "folding":
                root.style.height = inner.clientHeight + 'px';
                this.state = "expanding";
                break;
        }
    }
}
</script>
