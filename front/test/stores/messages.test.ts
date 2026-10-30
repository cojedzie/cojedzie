import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import moment from "moment";
import { useMessagesStore } from "@/stores/messages";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const message = (id: string, type: string) => ({
    id,
    type,
    message: `Message ${id}`,
    validFrom: "2026-10-30T12:00:00+01:00",
    validTo: "2026-10-30T18:00:00+01:00",
    $refs: { stops: { items: [] }, lines: { items: [] } },
});

describe("messages store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        get.mockReset();
    });

    it("requests all messages with their stops and lines", async () => {
        get.mockResolvedValue({ data: [] });

        await useMessagesStore().update();

        expect(get).toHaveBeenCalledWith("v1_message_all", {
            version: "^1.0",
            query: { embed: "$refs.stops,$refs.lines" },
        });
    });

    it("parses the validity into local moments and becomes ready", async () => {
        get.mockResolvedValue({ data: [message("1", "info")] });
        const store = useMessagesStore();
        const before = moment();

        await store.update();

        expect(store.state).toBe("ready");
        expect(store.lastUpdate.isSameOrAfter(before)).toBe(true);
        expect(store.messages[0].validFrom.isSame(moment("2026-10-30T11:00:00Z"))).toBe(true);
        expect(store.messages[0].validTo.isSame(moment("2026-10-30T17:00:00Z"))).toBe(true);
        expect(store.messages[0].validFrom.utcOffset()).toBe(moment().utcOffset());
    });

    it("counts the messages by type", async () => {
        get.mockResolvedValue({
            data: [message("1", "info"), message("2", "breakdown"), message("3", "info"), message("4", "unknown")],
        });
        const store = useMessagesStore();

        await store.update();

        expect(store.count).toBe(4);
        expect(store.counts).toEqual({ info: 2, breakdown: 1, unknown: 1 });
    });

    it("sets the error when the request fails", async () => {
        get.mockRejectedValue({ status: 500 });
        const store = useMessagesStore();

        await store.update();

        expect(store.state).toBe("error");
        expect(store.error).toBe(JSON.stringify({ status: 500 }));
    });
});
