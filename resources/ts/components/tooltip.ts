import Vue from 'vue';
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";

@Component({ template: require('../../components/tooltip.html') })
export class TooltipComponent extends Vue {
    @Prop({ type: String, default: "auto" }) public placement: string;
    public show: boolean = false;
    public element: Element = null;

    private _events: { [event: string]: any };

    mounted() {
        this.$el.parentElement.addEventListener('mouseenter', this._events['mouseenter'] = () => this.show = true);
        this.$el.parentElement.addEventListener('mouseleave', this._events['mouseleave'] = () => this.show = false);

        this.element = this.$el.parentElement;
    }

    beforeDestroy() {
        this.$el.parentElement.removeEventListener('mouseenter', this._events['mouseenter']);
        this.$el.parentElement.removeEventListener('mouseleave', this._events['mouseleave']);
    }
}

Vue.component('Tooltip', TooltipComponent);
