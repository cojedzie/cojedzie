import { DOMParser, Element, Node, XMLSerializer } from "@xmldom/xmldom";
import fs from "fs";
import path from "path";

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

function clean(element: Element, referenced: Set<string>, prefix: string) {
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

        // icons are inlined into one document, so the ids they keep (masks, clip paths) are prefixed with the
        // icon's name, or an icon would use the first mask of that id in the page, which may belong to another one
        for (const attribute of Array.from(child.attributes)) {
            if (attribute.name === "id") {
                child.setAttribute("id", `${prefix}-${attribute.value}`);
            } else if (attribute.value.includes("#")) {
                child.setAttribute(
                    attribute.name,
                    attribute.value.replace(/#([\w.:-]+)/g, (match, id) =>
                        referenced.has(id) ? `#${prefix}-${id}` : match,
                    ),
                );
            }
        }

        child.removeAttribute("data-name");

        clean(child, referenced, prefix);
    }
}

// `name` (the file name of the icon) makes the ids the icon keeps unique in the page
export function parseSvgIcon(source: string, name: string): SvgIcon {
    const svg = new DOMParser().parseFromString(source, "image/svg+xml").documentElement;
    // `#…` also matches colours, so only the ids that are both defined and referenced count
    const defined = new Set<string>(Array.from(source.matchAll(/\sid="([^"]+)"/g), match => match[1]));
    const referenced = new Set<string>(
        Array.from(source.matchAll(/#([\w.:-]+)/g), match => match[1]).filter(id => defined.has(id)),
    );

    clean(svg, referenced, name);

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

            const icon = parseSvgIcon(
                fs.readFileSync(filename, { encoding: "utf-8" }),
                `icon-${path.basename(filename, ".svg")}`,
            );

            return {
                code: `export default ${JSON.stringify(icon)};`,
                // generated code has nothing to map back to, an empty map says so and keeps the bundle's maps valid
                map: { mappings: "" },
            };
        },
    };
}
