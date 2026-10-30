import { describe, expect, it } from "vitest";
import type { UserStateStorage } from "@/stores/persistence/storage";

export function describeUserStateStorage(name: string, create: () => UserStateStorage): void {
    describe(name, () => {
        it("loads undefined for a missing key", async () => {
            expect(await create().load("missing")).toBeUndefined();
        });

        it("loads what was saved", async () => {
            const storage = create();
            const value = { entries: [{ id: "1", tags: ["a", "b"] }], date: "2026-10-30T10:00:00.000Z" };

            await storage.save("key", value);

            expect(await storage.load("key")).toEqual(value);
        });

        it("forgets a removed key", async () => {
            const storage = create();

            await storage.save("key", { a: 1 });
            await storage.remove("key");

            expect(await storage.load("key")).toBeUndefined();
        });

        it("returns copies", async () => {
            const storage = create();
            const value = { list: [1, 2, 3] };

            await storage.save("key", value);
            const loaded = await storage.load<{ list: number[] }>("key");
            loaded!.list.push(4);
            value.list.push(5);

            expect(await storage.load("key")).toEqual({ list: [1, 2, 3] });
        });

        it("keeps keys independent", async () => {
            const storage = create();

            await storage.save("a", 1);
            await storage.save("b", 2);
            await storage.remove("a");

            expect(await storage.load("a")).toBeUndefined();
            expect(await storage.load("b")).toBe(2);
        });
    });
}
