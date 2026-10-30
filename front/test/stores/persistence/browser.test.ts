// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { BrowserStorage } from "@/stores/persistence/browser";
import { describeUserStateStorage } from "./storage.contract";

beforeEach(() => {
    localStorage.clear();
    sessionStorage.clear();
});

describeUserStateStorage("BrowserStorage on localStorage", () => new BrowserStorage(localStorage));
describeUserStateStorage("BrowserStorage on sessionStorage", () => new BrowserStorage(sessionStorage));

describe("BrowserStorage", () => {
    it("stores values as JSON under the key", async () => {
        const value = { a: [1, 2], b: "2026-10-30T10:00:00.000Z" };

        await new BrowserStorage(localStorage).save("k", value);

        expect(localStorage.getItem("k")).toBe(JSON.stringify(value));
    });

    it("loads invalid JSON as undefined and leaves it in place", async () => {
        localStorage.setItem("k", "{not json");

        expect(await new BrowserStorage(localStorage).load("k")).toBeUndefined();
        expect(localStorage.getItem("k")).toBe("{not json");
    });
});
