import Vue from 'vue';
import { Component, Prop, Watch } from "vue-property-decorator";
import Popper, { Placement } from "popper.js";

@Component({ template: require("../../components/popper.html") })
export class PopperComponent extends Vue {
    @Prop(String)
    public reference: string;

    @Prop({ type: String, default: "auto" })
    public placement: Placement;

    @Prop(Boolean)
    public arrow: boolean;

    @Prop({ type: Boolean, default: false })
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    public hovered: boolean = false;

    private _popper;

    get show() {
        return this.visible || this.hovered;
    }

    mounted() {
        const reference = this.$parent.$refs[this.reference] as HTMLElement;

        this._popper = new Popper(reference, this.$el, {
            placement: this.placement,
            modifiers: {
                arrow: { enabled: this.arrow, element: this.$refs['arrow'] as Element },
                responsive: {
                    enabled: true,
                    order: 890,
                    fn(data) {
                        if (window.innerWidth < 560) {
                            data.instance.options.placement = 'bottom';
                            data.styles.transform = `translate3d(0, ${data.offsets.popper.top}px, 0)`;
                            data.styles.width = '100%';
                            data.styles.margin = '0';
                            data.styles.right = '0';
                            data.styles.left = '0';
                            data.styles.width = 'auto';
                            data.arrowStyles.left = `${data.offsets.popper.left + data.offsets.arrow.left}px`;
                        }

                        return data;
                    }
                }
            }
        });

        this.$nextTick(() => this._popper.update())
    }

    updated() {
        this._popper.update();
    }

    @Watch('visible')
    private onVisibilityUpdate() {
        this._popper.update();
        window.dispatchEvent(new Event('resize'));
    }

    destroyed() {
        this._popper.destroy();
    }
}

@Component({ template: require('../../components/fold.html') })
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

    destroyed() {
        this.observer.disconnect();
    }

    @Watch('visible')
    private resize() {
        const inner = this.$refs['inner'] as HTMLDivElement;
        this.$el.style.height = `${(this.visible && inner) ? inner.clientHeight : 0}px`;
    }
}

@Component({ template: require("../../components/lazy.html") })
export class LazyComponent extends Vue {
    @Prop(Boolean)
    public    activate: boolean;
    protected visible:  boolean = false;

    @Watch('activate')
    private onVisibilityChange(value, old) {
        this.visible = value || old;
    }
}

Vue.component('Popper', PopperComponent);
Vue.component('Fold', FoldComponent);
Vue.component('Lazy', LazyComponent);
