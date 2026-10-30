import { describe, expect, it } from "vitest";
import { readFileSync } from "fs";
import path from "path";
import { compileString } from "sass";

const styles = path.resolve(__dirname, "../../styles");

// The --name: value pairs of the @theme block in tailwind.css
function tailwindTheme(): Record<string, string> {
    const css = readFileSync(path.join(styles, "tailwind.css"), "utf8");
    const theme = css.match(/@theme\s*{([^}]*)}/)[1];

    return Object.fromEntries([...theme.matchAll(/--([\w-]+):\s*([^;]+);/g)].map(([, name, value]) => [name, value]));
}

// Values of the given Sass expressions, compiled against _variables.scss
function sass(expressions: Record<string, string>): Record<string, string> {
    const declarations = Object.entries(expressions)
        .map(([name, expression]) => `--${name}: #{${expression}};`)
        .join("\n");

    const { css } = compileString(`@import "variables";\n:root { ${declarations} }`, {
        loadPaths: [styles],
        silenceDeprecations: ["import", "global-builtin", "color-functions", "if-function"],
    });

    return Object.fromEntries([...css.matchAll(/--([\w-]+):\s*([^;]+);/g)].map(([, name, value]) => [name, value]));
}

describe("Tailwind theme", () => {
    it("uses the same breakpoints and colours as the Sass variables", () => {
        expect(tailwindTheme()).toEqual(
            sass({
                "breakpoint-sm": "map-get($grid-breakpoints, sm)",
                "breakpoint-md": "map-get($grid-breakpoints, md)",
                "breakpoint-lg": "map-get($grid-breakpoints, lg)",
                "breakpoint-xl": "map-get($grid-breakpoints, xl)",
                "color-primary": "$primary",
                "color-danger": "$danger",
                "color-dark": "$dark",
                "color-light": "$light",
                "color-muted": "$text-muted",
            }),
        );
    });
});
