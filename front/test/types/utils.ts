// Type-level tests, checked by `npm run check:types` (vue-tsc), not run by Vitest.
import { set } from "@/utils";

const state = { visibility: { map: false, details: { open: false } }, count: 0 };

set(state, "count", 1);
set(state, "visibility.map", true);
set(state, "visibility.details.open", true);

// @ts-expect-error: no such property
set(state, "missing", 1);
// @ts-expect-error: no such nested property
set(state, "visibility.missing", true);
// @ts-expect-error: the value has the wrong type for the path
set(state, "visibility.map", "yes");

// a dictionary still accepts any key, as the v-hover directive needs
const dictionary: Record<string, unknown> = {};
set(dictionary, "anything", true);
