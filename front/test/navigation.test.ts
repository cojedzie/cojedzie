// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";
import { navigation } from "@/navigation";
import { routes } from "@/routes";

describe("navigation", () => {
    it("every entry points at a registered route", () => {
        const router = createRouter({ routes, history: createMemoryHistory() });

        for (const entry of navigation) {
            expect(router.hasRoute(entry.route), entry.id).toBe(true);
        }
    });
});
