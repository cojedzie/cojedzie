// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from "vitest";
import { useAboutLinks } from "@/composables/useAboutLinks";

function linksIn(production: boolean) {
    vi.stubGlobal("CoJedzie", { version: "1.2.3", production });
    return useAboutLinks().links.map(link => link.href);
}

describe("useAboutLinks", () => {
    afterEach(() => vi.unstubAllGlobals());

    it("links Storybook only outside production", () => {
        expect(linksIn(false)).toContain("/storybook/");
        expect(linksIn(true)).not.toContain("/storybook/");
    });
});
