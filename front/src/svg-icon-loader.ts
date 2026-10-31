import { DOMParser, Element, Node, XMLSerializer } from "@xmldom/xmldom";
import fs from "fs";

type SvgIconLoaderOptions = {
    match: RegExp;
    exclude?: RegExp;
};

// what an imported icon is, see the `@resources/icons/*.svg` module declaration
export type SvgIcon = {
    viewBox: string;
    // markup of everything inside the root <svg>
    content: string;
};

// elements that describe the drawing instead of being part of it
const metadata = ["title", "desc", "metadata"];

function clean(element: Element, referenced: Set<string>) {
    for (const node of Array.from(element.childNodes)) {
        if (node.nodeType === Node.COMMENT_NODE || metadata.includes(node.localName)) {
            element.removeChild(node);
            continue;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            continue;
        }

        const child = node as Element;

        // exported ids (layer names and such) would repeat in the page for every rendered icon
        if (child.hasAttribute("id") && !referenced.has(child.getAttribute("id"))) {
            child.removeAttribute("id");
        }

        child.removeAttribute("data-name");

        clean(child, referenced);
    }
}

export function parseSvgIcon(source: string): SvgIcon {
    const svg = new DOMParser().parseFromString(source, "image/svg+xml").documentElement;
    const referenced = new Set<string>(Array.from(source.matchAll(/#([\w.:-]+)/g), match => match[1]));

    clean(svg, referenced);

    const serializer = new XMLSerializer();

    return {
        viewBox: svg.getAttribute("viewBox"),
        content: Array.from(svg.childNodes)
            .map(node => serializer.serializeToString(node))
            .join("")
            // children inherit the namespace from the <svg> they are rendered in
            .replace(/ xmlns(:\w+)?="[^"]*"/g, "")
            .trim(),
    };
}

export default function ({ match, exclude }: SvgIconLoaderOptions) {
    return {
        name: "svg-icon-loader",
        async transform(_, filename) {
            if (!match.test(filename) || (exclude && exclude.test(filename))) {
                return null;
            }

            const icon = parseSvgIcon(fs.readFileSync(filename, { encoding: "utf-8" }));

            return {
                code: `export default ${JSON.stringify(icon)};`,
                // generated code has nothing to map back to, an empty map says so and keeps the bundle's maps valid
                map: { mappings: "" },
            };
        },
    };
}
