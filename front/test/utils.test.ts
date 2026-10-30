import { describe, expect, it } from "vitest";
import { set } from "../src/utils";

describe("set", () => {
    it("sets a top-level property", () => {
        const object = { hovered: false };

        set(object, "hovered", true);

        expect(object).toEqual({ hovered: true });
    });

    it("follows the whole path to set a nested property", () => {
        const object = { a: { b: { c: 1 } }, b: { c: 1 } };

        set(object, "a.b.c", 2);

        expect(object).toEqual({ a: { b: { c: 2 } }, b: { c: 1 } });
    });
});
