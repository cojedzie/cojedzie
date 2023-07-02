import { describe, expect, it } from "vitest";
import createServiceContainer from "../src/utils/container";

class Foo {}
class Baz {}
class Bar {
    constructor(readonly foo: Foo, readonly baz: Baz) {}
}

type Services = {
    foo: Foo;
    baz: Baz;
    bar: Bar;
};

describe("service container", () => {
    const container = createServiceContainer<Services>({
        foo: () => new Foo(),
        baz: () => new Baz(),
        bar: container => new Bar(container.get("foo"), container.get("baz")),
    });

    it("provides foo service", () => {
        expect(container.get("foo")).toBeInstanceOf(Foo);
    });

    it("provides baz service", () => {
        expect(container.get("baz")).toBeInstanceOf(Baz);
    });

    it("provides bar service", () => {
        const service = container.get("bar");
        expect(service).toBeInstanceOf(Bar);

        expect(service.foo).toBe(container.get("foo"));
        expect(service.baz).toBe(container.get("baz"));
    });
});
