import { ContainerKey, Services } from "@/services";
import { inject } from "vue";

export default function useService<TService extends keyof Services>(service: TService): Services[TService] {
    const container = inject(ContainerKey);
    return container.get(service);
}
