import Vue from 'vue';
import { Component, Prop, Watch } from "vue-property-decorator";


@Component({ template: require('@templates/fold.html') })
export class FoldComponent extends Vue {
    private observer: MutationObserver;

    @Prop(Boolean)
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    mounted() {
        this.resize();

        this.observer = new MutationObserver(() => this.resize());
        this.observer.observe(this.$refs['inner'] as Node, {
            characterData: true,
            subtree: true,
            childList: true
        });
    }

    beforeDestroy() {
        this.observer.disconnect();
    }

    @Watch('visible')
    private resize() {
        const inner = this.$refs['inner'] as HTMLDivElement;
        (this.$el as HTMLElement).style.height = `${(this.visible && inner) ? inner.clientHeight : 0}px`;
    }
}

@Component({ template: require("@templates/lazy.html") })
export class LazyComponent extends Vue {
    @Prop(Boolean)
    public    activate: boolean;
    protected visible:  boolean = false;

    @Watch('activate')
    private onVisibilityChange(value, old) {
        this.visible = value || old;
    }
}

Vue.component('Fold', FoldComponent);
Vue.component('Lazy', LazyComponent);

// https://github.com/vuejs/vue/issues/7829
Vue.component('Empty', {
    functional: true,
    render: (h, { data }) => h('template', data, '')
});
