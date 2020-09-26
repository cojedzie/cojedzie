import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import Popper, { Placement } from "popper.js";
import { defaultBreakpoints } from "../../filters";

/**
 * How popup will be presented to user:
 *  - "modal" - modal window
 *  - "popup" - simple popup
 */
export type DialogBehaviour = "modal" | "popup";

@Component({
    template: require('../../../components/ui/dialog.html'),
    inheritAttrs: false,
})
export default class UiDialog extends Vue {
    @Prop({ type: String, default: "popup" })
    private behaviour: DialogBehaviour;

    @Prop({ type: String })
    private mobileBehaviour: DialogBehaviour;

    @Prop([String, HTMLElement])
    public reference: string | HTMLElement;

    @Prop(Object)
    public refs: string;

    @Prop({ type: String, default: "auto" })
    public placement: Placement;

    @Prop(Boolean)
    public arrow: boolean;

    @Prop({ type: Boolean, default: true })
    public responsive: boolean;

    @Prop(String)
    public title: string;

    private isMobile: boolean = false;

    private _focusOutEvent;
    private _resizeEvent;

    private _popper;

    get currentBehaviour(): DialogBehaviour {
        if (!this.mobileBehaviour) {
            return this.behaviour;
        }

        return this.isMobile ? this.mobileBehaviour : this.behaviour;
    }

    get hasFooter() {
        return this.$hasSlot('footer')
    }

    get hasHeader() {
        return this.$hasSlot('header')
    }

    private getReferenceElement() {
        const isInWrapper = this.$parent.$options.name == 'portalTarget';

        if (typeof this.reference === 'string') {
            if (this.refs) {
                return this.refs[this.reference];
            }

            if (isInWrapper) {
                return this.$parent.$parent.$refs[this.reference];
            }

            return this.$parent.$refs[this.reference];
        }

        if (this.reference instanceof HTMLElement) {
            return this.reference;
        }

        return isInWrapper ? this.$parent.$el : this.$el.parentElement;
    }

    focusOut(event: MouseEvent) {
        if (this.$el.contains(event.target as Node)) {
            return;
        }

        this.$emit('leave', event);
    }

    mounted() {
        this.handleWindowResize();

        if (this.behaviour === 'popup') {
            this.initPopper();
        }

        window.addEventListener('resize', this._resizeEvent = this.handleWindowResize.bind(this));
    }

    private initPopper() {
        const reference = this.getReferenceElement();

        this._popper = new Popper(reference, this.$el, {
            placement: this.placement,
            modifiers: {
                arrow: { enabled: this.arrow, element: this.$refs['arrow'] as Element },
                responsive: {
                    enabled: this.responsive,
                    order: 890,
                    fn(data) {
                        if (window.innerWidth < 560) {
                            data.instance.options.placement = 'top';
                            data.styles.transform = `translate3d(0, ${ data.offsets.popper.top }px, 0)`;
                            data.styles.right = '0';
                            data.styles.left = '0';
                            data.styles.width = 'auto';
                            data.arrowStyles.left = `${ data.offsets.popper.left + data.offsets.arrow.left }px`;
                        }

                        return data;
                    }
                }
            }
        });

        this.$nextTick(() => {
            this._popper && this._popper.update();
            document.addEventListener('click', this._focusOutEvent = this.focusOut.bind(this), { capture: true });
        });
    }

    private removePopper() {
        this._popper.destroy()
        this._popper = null;
    }

    updated() {
        if (this._popper) {
            this._popper.update();
        }
    }

    beforeDestroy() {
        this._focusOutEvent && document.removeEventListener('click', this._focusOutEvent, { capture: true });
    }

    removed() {
        if (this._popper) {
            this.removePopper();
        }
    }

    private handleBackdropClick(ev: Event) {
        const target = ev.target as HTMLElement;

        if (target.classList.contains("ui-backdrop")) {
            this.$emit('leave');
        }
    }

    private handleCloseClick() {
        this.$emit('leave');
        this.$emit('close');
    }

    private handleWindowResize() {
        this.isMobile = screen.width < defaultBreakpoints.md;
    }

    @Watch('currentBehaviour')
    private handleBehaviourChange(newBehaviour: DialogBehaviour, oldBehaviour: DialogBehaviour) {
        if (oldBehaviour === 'popup') {
            this.removePopper();
        }

        if (newBehaviour === 'popup') {
            this.$nextTick(() => this.initPopper());
        }
    }
}

Vue.component("ui-dialog", UiDialog);
