import Vue from 'vue';
import Component from "vue-class-component";
import { Prop, Watch } from "vue-property-decorator";

type Events = {
    [evnet: string]: (...any) => void,
}

type Trigger = "hover" | "focus" | "long-press";

const longPressTimeout = 1000;

@Component({ template: require('@templates/tooltip.html') })
export class TooltipComponent extends Vue {
    @Prop({ type: String, default: "top" }) public placement: string;
    @Prop({ type: Number, default: 400 }) public delay: number;
    @Prop({ type: Array, default: () => ["hover", "focus", "long-press"]}) public triggers: Trigger[];

    public show: boolean = false;
    public root: HTMLElement = null;

    private _events: Events = {};

    mounted() {
        this.root = (this.$refs['root'] as HTMLSpanElement).parentElement;
        this.updateTriggers();
    }

    beforeDestroy() {
        this._removeEventListeners();
    }

    @Watch('triggers')
    updateTriggers() {
        this._removeEventListeners();

        this._events = {};

        let blocked: boolean = false;

        if (this.triggers.includes("hover") && !this.$isTouch) {
            let timeout;

            this._events['mouseenter'] = () => {
                timeout = window.setTimeout(() => { this.show = !blocked }, this.delay);
            };

            this._events['mouseleave'] = () => {
                window.clearTimeout(timeout);
                this.show = false
            };
        }

        if (this.triggers.includes("focus") || (this.triggers.includes("hover") && this.$isTouch)) {
            if (this.$isTouch) {
                this._events['touchstart'] = () => {
                    // this is to prevent showing tooltips after tap
                    blocked = true;
                    setTimeout(() => blocked = false, longPressTimeout - 50);
                }
            }

            this._events['focus'] = () => {
                this.show = !blocked;
            };

            this._events['blur'] = () => {
                this.show = false
            };
        }

        if (this.triggers.includes("long-press") && this.$isTouch) {
            let timeout;

            this._events['touchstart'] = () => {
                timeout = window.setTimeout(() => { this.show = true }, longPressTimeout);

                // this is to prevent showing tooltips after tap
                blocked = true;
                setTimeout(() => blocked = false, longPressTimeout - 50);
            };

            this._events['touchend'] = ev => {
                window.clearTimeout(timeout);

                if (this.show) {
                    ev.preventDefault();
                    this.root.focus();
                }
            };

            this._events['blur'] = () => {
                this.show = false
            };
        }

        this._registerEventListeners();
    }

    private _registerEventListeners() {
        for (const [event, handler] of Object.entries(this._events)) {
            this.root.addEventListener(event, handler);
        }
    }

    private _removeEventListeners() {
        for (const [event, handler] of Object.entries(this._events)) {
            this.root.removeEventListener(event, handler);
        }
    }
}

Vue.component('Tooltip', TooltipComponent);
