import Vue from 'vue';
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";

@Component({ template: require('../../components/tooltip.html') })
export class TooltipComponent extends Vue {
    @Prop({ type: String, default: "auto" }) public placement: string;
    @Prop({ type: Number, default: 100 }) public delay: number;

    public show: boolean = false;
    public element: Element = null;

    private _events: { [event: string]: any };
    private _timeout: number;

    mounted() {
        this.$el.parentElement.addEventListener('mouseenter', this._events['mouseenter'] = () => {
            this._timeout = window.setTimeout(() => { this.show = true }, this.delay);
        });

        this.$el.parentElement.addEventListener('mouseleave', this._events['mouseleave'] = () => {
            window.clearTimeout(this._timeout);
            this.show = false
        });

        this.element = this.$el.parentElement;
    }

    beforeDestroy() {
        this.$el.parentElement.removeEventListener('mouseenter', this._events['mouseenter']);
        this.$el.parentElement.removeEventListener('mouseleave', this._events['mouseleave']);
    }
}

Vue.component('Tooltip', TooltipComponent);
