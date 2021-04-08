import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import Popper, { Placement } from "popper.js";
import { defaultBreakpoints } from "@/filters";

/**
 * How popup will be presented to user:
 *  - "modal" - modal window
 *  - "popup" - simple popup
 */
export type DialogBehaviour = "modal" | "popup";

let openModalCounter: number = 0;

function computeZIndexOfElement(element: HTMLElement): number {
    let current = element;

    while (true) {
        const zIndex = window.getComputedStyle(current).zIndex;

        if (zIndex !== "auto") {
            return parseInt(zIndex);
        }

        if (!current.parentElement) {
            break;
        }

        current = current.parentElement;
    }

    return 0;
}

@Component({
    inheritAttrs: false,
    template: require('@templates/ui/dialog.html'),
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

    /** Inherited class hack */
    private staticClass: string[] = [];

    private zIndex: number = 1000;

    private _focusOutEvent;
    private _resizeEvent;

    private _popper;

    get attrs() {
        return {
            ...this.$attrs,
            "class": this.staticClass
        }
    }

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
            if (this.reference[0] === '#') {
                return document.getElementById(this.reference.substr(1));
            }

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
        this.zIndex = computeZIndexOfElement(this.getReferenceElement()) + 100;

        this.handleWindowResize();

        if (this.behaviour === 'popup') {
            this.mountPopper();
        }

        this.staticClass = Array.from(this.$el.classList).filter(cls => ["ui-backdrop", "ui-popup", "ui-popup--arrow"].indexOf(cls) === -1);

        window.addEventListener('resize', this._resizeEvent = this.handleWindowResize.bind(this));

        this._activated();
    }

    private _activated() {
        if (this.behaviour === 'modal') {
            this.mountModal();
        }
    }

    private _deactivated() {
        if (this.behaviour === 'modal') {
            this.dismountModal();
        }
    }

    private mountModal() {
        if (openModalCounter === 0) {
            document.body.style.paddingRight = `${window.screen.width - document.body.clientWidth}px`
            document.body.classList.add('contains-modal');
        }

        openModalCounter++;
    }

    private dismountModal() {
        openModalCounter--;

        if (openModalCounter === 0) {
            document.body.style.paddingRight = "";
            document.body.classList.remove('contains-modal');
        }
    }

    activated() {
        this._activated();
    }

    deactivated() {
        this._deactivated();
    }

    private mountPopper() {
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

        this._deactivated()
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
            this.$nextTick(() => this.mountPopper());
        }

        if (newBehaviour === 'modal') {
            this.mountModal();
        }

        if (oldBehaviour === 'modal') {
            this.dismountModal();
        }
    }
}

Vue.component("ui-dialog", UiDialog);
