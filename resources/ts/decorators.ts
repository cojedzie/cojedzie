export interface Decorator<TArgs extends any[], FArgs extends any[], TRet extends any, FRet extends any> {
    decorate(f: (...farg: FArgs) => any, ...args: TArgs): (...farg: FArgs) => TRet;

    (...args: TArgs): (target, name: string | symbol, descriptor: TypedPropertyDescriptor<(...farg: FArgs) => FRet>) => void;
}

export function decorator<TArgs extends any[], FArgs extends any[], TRet extends any, FRet extends any>
    (decorate: (f: (...farg: FArgs) => FRet, ...args: TArgs) => (...farg: FArgs) => TRet)
    : Decorator<TArgs, FArgs, TRet, FRet> {

    const factory = function (this: Decorator<TArgs, FArgs, TRet, FRet>, ...args: TArgs) {
        return (target, name: string | symbol, descriptor: PropertyDescriptor) => {
            descriptor.value = decorate(descriptor.value, ...args);
        }
    } as Decorator<TArgs, FArgs, TRet, FRet>;
    factory.decorate = decorate;

    return factory;
}

export const throttle = decorator(function (decorated, time: number) {
    let timeout;
    return function (this: any, ...args) {
        if (typeof timeout === 'undefined') {
            timeout = window.setTimeout(() => {
                decorated.call(this, ...args);
                timeout = undefined;
            }, time);
        }
    }
});

export const debounce = decorator(function (decorated, time: number, max: number = time * 3) {
    let timeout;
    return function (this: any, ...args) {
        if (typeof timeout !== 'undefined') {
            window.clearTimeout(timeout);
        }

        timeout = window.setTimeout(() => {
            timeout = undefined;
            decorated.call(this, ...args);
        }, time);
    }
});

export const condition = decorator(function <Args extends any[], Ret extends any>(decorated: (...args: Args) => Ret, predicate: (...args: Args) => boolean) {
    return function (this: any, ...args: Args) {
        if (predicate(...args)) {
            return decorated(...args);
        }
    }
});
