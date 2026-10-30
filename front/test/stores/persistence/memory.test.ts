import { MemoryStorage } from "@/stores/persistence/memory";
import { describeUserStateStorage } from "./storage.contract";

describeUserStateStorage("MemoryStorage", () => new MemoryStorage());
