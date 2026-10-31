import { describe, expect, it } from "vitest";
import { parseSvgIcon } from "../src/svg-icon-loader";

// the shape of what Illustrator / Vectorcraft export
const exported = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">
    <title>bus</title>
    <!-- Generator: Vectorcraft -->
    <defs><clipPath id="clip"><rect width="24" height="24"/></clipPath></defs>
    <g id="Layer_1" data-name="Layer 1" clip-path="url(#clip)">
        <path d="M4 18 L20 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <g><path d="M4 12 L20 12" fill="none" stroke="currentColor" stroke-width="2"/></g>
    </g>
</svg>`;

describe("parseSvgIcon", () => {
    const icon = parseSvgIcon(exported);

    it("keeps the view box and every drawn element with its attributes", () => {
        expect(icon.viewBox).toBe("0 0 24 24");
        expect(icon.content).toContain(
            '<path d="M4 18 L20 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        );
        expect(icon.content).toContain('<path d="M4 12 L20 12"');
    });

    it("drops metadata and ids nothing refers to", () => {
        expect(icon.content).not.toMatch(/<title|Generator|Layer_1|data-name|xmlns/);
        expect(icon.content).toContain('<clipPath id="clip">');
    });
});
