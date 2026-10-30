// @vitest-environment happy-dom
/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, defineStore, setActivePinia } from "pinia";
import { createApp, reactive, ref } from "vue";
import { MemoryStorage } from "@/stores/persistence/memory";
import type { UserStateStorage } from "@/stores/persistence/storage";
import { flushPersistence, persistence } from "@/stores/persistence/plugin";

let counter = 0;

function freshPinia(debounce?: number) {
    // Pinia defers plugins until it is installed in an app.
    const pinia = createPinia();
    pinia.use(persistence({ debounce }));
    createApp({}).use(pinia);
    setActivePinia(pinia);

    return pinia;
}

function setup(storage: UserStateStorage, extra: object = {}, debounce?: number) {
    const pinia = freshPinia(debounce);

    const useStore = defineStore(
        `test-${counter++}`,
        () => {
            const count = ref(0);
            const other = ref("a");
            return { count, other };
        },
        { persist: { key: "test", storage, ...extra } },
    );

    return useStore(pinia);
}

describe("persistence plugin", () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(async () => {
        await flushPersistence();
        vi.useRealTimers();
    });

    it("hydrates from saved state", async () => {
        const store = setup(new MemoryStorage({ test: { count: 5, other: "b" } }));
        await store.$hydrated;

        expect(store.count).toBe(5);
        expect(store.other).toBe("b");
    });

    it("keeps defaults when nothing is saved", async () => {
        const store = setup(new MemoryStorage());
        await store.$hydrated;

        expect(store.count).toBe(0);
        expect(store.other).toBe("a");
    });

    it("does not save before hydration finishes", async () => {
        const inner = new MemoryStorage({ test: { count: 5, other: "b" } });
        let release!: () => void;
        const gate = new Promise<void>(resolve => (release = resolve));
        const save = vi.spyOn(inner, "save");
        const storage: UserStateStorage = {
            load: async key => {
                await gate;
                return inner.load(key);
            },
            save: (key, value) => inner.save(key, value),
            remove: key => inner.remove(key),
        };

        const store = setup(storage);
        store.count = 1;
        await vi.advanceTimersByTimeAsync(5000);
        expect(save).not.toHaveBeenCalled();

        release();
        await store.$hydrated;
        await vi.advanceTimersByTimeAsync(5000);

        expect(save).not.toHaveBeenCalled();
        expect(await inner.load("test")).toEqual({ count: 5, other: "b" });
    });

    it("saves after changes, debounced", async () => {
        const storage = new MemoryStorage();
        const save = vi.spyOn(storage, "save");
        const store = setup(storage);
        await store.$hydrated;

        store.count = 1;
        await vi.advanceTimersByTimeAsync(200);
        store.count = 2;
        await vi.advanceTimersByTimeAsync(499);
        expect(save).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(1);
        expect(save).toHaveBeenCalledTimes(1);
        expect(save).toHaveBeenCalledWith("test", { count: 2, other: "a" });
    });

    it("saves only the picked part", async () => {
        const storage = new MemoryStorage();
        const save = vi.spyOn(storage, "save");
        const store = setup(storage, {
            pick: (state: { count: number }) => ({ count: state.count }),
        });
        await store.$hydrated;

        store.count = 3;
        await vi.advanceTimersByTimeAsync(500);

        expect(save).toHaveBeenCalledWith("test", { count: 3 });
    });

    it("flushes a pending save when the page is hidden", async () => {
        const storage = new MemoryStorage();
        const save = vi.spyOn(storage, "save");
        const store = setup(storage);
        await store.$hydrated;

        store.count = 1;
        Object.defineProperty(document, "visibilityState", { value: "hidden", configurable: true });
        document.dispatchEvent(new Event("visibilitychange"));
        await vi.advanceTimersByTimeAsync(0);
        expect(save).toHaveBeenCalledTimes(1);

        store.count = 2;
        window.dispatchEvent(new Event("pagehide"));
        await vi.advanceTimersByTimeAsync(0);
        expect(save).toHaveBeenCalledTimes(2);
        expect(save).toHaveBeenLastCalledWith("test", { count: 2, other: "a" });
    });

    describe("when restoring fails", () => {
        beforeEach(() => {
            vi.spyOn(console, "error").mockImplementation(() => undefined);
        });

        it("does not save when loading fails", async () => {
            const storage = new MemoryStorage({ test: { count: 5 } });
            const save = vi.spyOn(storage, "save");
            vi.spyOn(storage, "load").mockRejectedValue(new Error("offline"));
            const store = setup(storage);

            await expect(store.$hydrated).resolves.toBeUndefined();
            store.count = 1;
            await vi.advanceTimersByTimeAsync(5000);
            await flushPersistence();

            expect(save).not.toHaveBeenCalled();
        });

        it("does not save when restoring throws", async () => {
            const storage = new MemoryStorage({ test: { count: 5 } });
            const save = vi.spyOn(storage, "save");
            const store = setup(storage, {
                restore: () => {
                    throw new Error("bad shape");
                },
            });

            await expect(store.$hydrated).resolves.toBeUndefined();
            store.count = 1;
            await vi.advanceTimersByTimeAsync(5000);
            await flushPersistence();

            expect(save).not.toHaveBeenCalled();
        });
    });

    it("leaves stores without persist alone", async () => {
        const pinia = freshPinia();
        const useStore = defineStore("plain", () => ({ state: reactive({ a: 1 }) }));
        const store = useStore(pinia);

        await expect(store.$hydrated).resolves.toBeUndefined();
    });
});
