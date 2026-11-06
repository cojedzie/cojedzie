// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { useNetworkStore } from "@/stores/network";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api/client/static", () => ({
    StaticClient: class {
        get = get;
    },
}));

class FakeEventSource {
    static CLOSED = 2;
    static instances: FakeEventSource[] = [];
    readyState = 1;
    listeners: Record<string, (event: unknown) => void> = {};
    close = vi.fn();

    constructor(public url: string) {
        FakeEventSource.instances.push(this);
    }

    addEventListener(name: string, listener: (event: unknown) => void) {
        this.listeners[name] = listener;
    }
}

const apiNode = (id: string) => ({
    id,
    url: `https://${id}.example`,
    endpoints: [{ name: "v1_x", template: "/x", version: "1.0" }],
});
const response = (nodes: unknown[], headers: Record<string, string> = {}) => ({ data: nodes, headers });

describe("network store", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.spyOn(Math, "random").mockReturnValue(0);
        vi.stubGlobal("CoJedzie", { api: { hub: "https://hub.example" } });
        vi.stubGlobal("EventSource", FakeEventSource);
        FakeEventSource.instances = [];
        get.mockReset();
        setActivePinia(createPinia());
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it("fills the node list on update, nodes being available", async () => {
        get.mockResolvedValue(response([apiNode("a"), apiNode("b")]));
        const store = useNetworkStore();

        await store.update();

        expect(store.available.map(node => node.id)).toEqual(["a", "b"]);
        expect(store.nodes.a.endpoints.v1_x).toBeDefined();
    });

    it("suspends a failed node with backoff and checks it again", async () => {
        get.mockResolvedValueOnce(response([apiNode("a"), apiNode("b")]));
        const store = useNetworkStore();
        await store.update();

        await store.nodeFailed("a");
        expect(store.available.map(node => node.id)).toEqual(["b"]);
        expect(store.nodes.a.failures).toBe(1);

        get.mockResolvedValueOnce({ data: {}, headers: {} });
        await vi.advanceTimersByTimeAsync(5000);

        expect(get).toHaveBeenLastCalledWith("v1_status_health", { version: "^1.0", base: "https://a.example" });
        expect(store.available.map(node => node.id)).toEqual(["a", "b"]);
        expect(store.nodes.a.failures).toBe(0);
    });

    it("keeps the node down when the check fails", async () => {
        get.mockResolvedValueOnce(response([apiNode("a")]));
        const store = useNetworkStore();
        await store.update();
        await store.nodeFailed("a");

        get.mockRejectedValue(new Error("down"));
        await vi.advanceTimersByTimeAsync(5000);

        expect(store.nodes.a.failures).toBe(2);
        expect(store.available).toEqual([]);
    });

    it("ignores failures of unknown nodes", async () => {
        const store = useNetworkStore();

        await store.nodeFailed("nope");
        await store.nodeCheck("nope");

        expect(get).not.toHaveBeenCalled();
    });

    it("reacts to node events from the hub", async () => {
        get.mockResolvedValue(
            response([apiNode("a")], { link: '<https://hub.example/.well-known/mercure>; rel="mercure"' }),
        );
        const store = useNetworkStore();
        await store.update();

        const [source] = FakeEventSource.instances;
        expect(source.url).toBe("https://hub.example/.well-known/mercure?topic=network%2Fnodes");

        const emit = (event: string, node: unknown) =>
            source.listeners.message({ data: JSON.stringify({ event, node }) });

        emit("node-joined", apiNode("b"));
        expect(Object.keys(store.nodes)).toEqual(["a", "b"]);

        emit("node-suspended", { id: "a" });
        expect(store.available.map(node => node.id)).toEqual(["b"]);

        emit("node-resumed", { id: "a" });
        expect(store.available.map(node => node.id)).toEqual(["a", "b"]);

        emit("node-left", { id: "a" });
        expect(Object.keys(store.nodes)).toEqual(["b"]);
    });

    describe("when the hub connection fails", () => {
        const hubResponse = () =>
            response([apiNode("a")], { link: '<https://hub.example/.well-known/mercure>; rel="mercure"' });

        const fail = (source: FakeEventSource) => {
            source.readyState = 2;
            source.listeners.error({});
        };

        const last = () => FakeEventSource.instances.at(-1)!;

        beforeEach(() => {
            get.mockResolvedValue(hubResponse());
        });

        it("fetches a fresh token before reconnecting", async () => {
            const store = useNetworkStore();
            await store.update();

            fail(last());
            expect(get).toHaveBeenCalledTimes(1);

            await vi.advanceTimersByTimeAsync(1000);

            expect(get).toHaveBeenCalledTimes(2);
            expect(get).toHaveBeenLastCalledWith("v1_network_nodes", { version: "^1.0" });
            expect(FakeEventSource.instances).toHaveLength(2);
        });

        it("waits longer after each consecutive failure", async () => {
            const store = useNetworkStore();
            await store.update();

            fail(last());
            await vi.advanceTimersByTimeAsync(1000);
            expect(FakeEventSource.instances).toHaveLength(2);

            fail(last());
            await vi.advanceTimersByTimeAsync(1999);
            expect(FakeEventSource.instances).toHaveLength(2);
            await vi.advanceTimersByTimeAsync(1);
            expect(FakeEventSource.instances).toHaveLength(3);

            fail(last());
            await vi.advanceTimersByTimeAsync(3999);
            expect(FakeEventSource.instances).toHaveLength(3);
            await vi.advanceTimersByTimeAsync(1);
            expect(FakeEventSource.instances).toHaveLength(4);
        });

        it("caps the delay between attempts", async () => {
            const store = useNetworkStore();
            await store.update();

            for (let attempt = 0; attempt < 20; attempt++) {
                fail(last());
                await vi.advanceTimersByTimeAsync(5 * 60 * 1000);
            }

            expect(FakeEventSource.instances).toHaveLength(21);
        });

        it("starts the backoff over once a connection opens", async () => {
            const store = useNetworkStore();
            await store.update();

            fail(last());
            await vi.advanceTimersByTimeAsync(1000);
            fail(last());
            await vi.advanceTimersByTimeAsync(2000);
            expect(FakeEventSource.instances).toHaveLength(3);

            last().listeners.open({});
            fail(last());
            await vi.advanceTimersByTimeAsync(1000);

            expect(FakeEventSource.instances).toHaveLength(4);
        });

        it("leaves reconnecting to the browser while the connection is only interrupted", async () => {
            const store = useNetworkStore();
            await store.update();

            last().readyState = 0;
            last().listeners.error({});
            await vi.advanceTimersByTimeAsync(60 * 1000);

            expect(FakeEventSource.instances).toHaveLength(1);
            expect(get).toHaveBeenCalledTimes(1);
        });
    });
});
