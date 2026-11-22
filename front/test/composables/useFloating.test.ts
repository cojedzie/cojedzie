// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from "vitest";
import { positionFloating } from "@/composables/useFloating";

describe("positionFloating", () => {
    let stop: (() => void) | undefined;

    afterEach(() => {
        stop?.();
        document.body.innerHTML = "";
    });

    it("pins a new floating element to the viewport until its first placement", async () => {
        const reference = document.body.appendChild(document.createElement("button"));
        const floating = document.body.appendChild(document.createElement("div"));

        stop = positionFloating(reference, floating);

        // focusing a control in it now must not scroll the page to the end, where the element sits unplaced
        expect(floating.style.position).toBe("fixed");

        await new Promise(resolve => setTimeout(resolve));

        expect(floating.style.position).toBe("absolute");
    });
});
