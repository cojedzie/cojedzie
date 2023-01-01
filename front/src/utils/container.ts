export type ServiceDefinition<TService, TContainer extends ServiceContainer<any>> = (container: TContainer) => TService;

export type ServiceDefinitions<TServices> = {
    [TService in keyof TServices]: ServiceDefinition<TServices[TService], ServiceContainer<TServices>>
}

export interface ServiceContainer<TServices> {
    get<TService extends keyof TServices>(service: TService): TServices[TService];
}

export default function createServiceContainer<TServices>(definitions: ServiceDefinitions<TServices>): ServiceContainer<TServices> {
    const services: Partial<TServices> = {};

    const container = {
        get<TService extends keyof TServices>(service: TService): TServices[TService] {
            if (!Object.hasOwn(services, service)) {
                services[service] = definitions[service](container);
            }

            return services[service];
        }
    }

    return container;
}
