import Vue from 'vue';
import { Component, Prop, Watch } from "vue-property-decorator";
import Popper, { Placement } from "popper.js";
import * as $ from 'jquery';

import popper   = require("../../components/popper.html");
import lazy     = require("../../components/lazy.html");
import collapse = require("../../components/fold.html");

@Component({ template: popper })
export class PopperComponent extends Vue {
    @Prop(String)
    public reference: string;

    @Prop({ type: String, default: "auto" })
    public placement: Placement;

    @Prop(Boolean)
    public arrow: boolean;

    @Prop({ type: Boolean, default: false })
    public visible: boolean;

    private _popper;

    mounted() {
        const reference = this.$parent.$refs[this.reference] as HTMLElement;

        this._popper = new Popper(reference, this.$el, {
            placement: this.placement,
            modifiers: {
                arrow: { enabled: this.arrow, element: this.$refs['arrow'] as Element }
            }
        });
    }

    @Watch('visible')
    private onVisibilityUpdate() {
        this._popper.update();
    }
}

@Component({ template: collapse })
export class FoldComponent extends Vue {
    @Prop(Boolean)
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    @Watch('visible')
    private onVisibilityChange(value) {
        const action = () => $(this.$el).collapse(value ? 'show' : 'hide');
        setTimeout(action);
    }
}

@Component({ template: lazy })
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
