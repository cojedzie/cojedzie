import {MaybeElementRef} from "@vueuse/core";
import Popper, {PopperOptions} from "popper.js"
import {ref, watch, Ref, unref, onUnmounted} from "vue";

export type UsePopperOptions = PopperOptions;
export type UsePopperResult = {
    popper: Ref<Popper>
}

export function usePopper(
    reference: MaybeElementRef<HTMLElement>,
    element: MaybeElementRef<HTMLElement>,
    options: UsePopperOptions = {}
): UsePopperResult {
    const popper = ref<Popper>();

    watch(
        [ element, reference ],
        () => {
            if (popper.value) {
                popper.value.destroy();
            }

            const newElement = unref(element);
            const newReference = unref(reference);

            if (!newElement || !newReference) {
                return;
            }

            popper.value = new Popper(newReference, newElement, options);
        },
        { immediate: true }
    )

    onUnmounted(() => {
        if (popper.value) {
            popper.value.destroy();
        }
    })

    return { popper }
}
