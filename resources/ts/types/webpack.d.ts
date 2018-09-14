declare module "*.html" {
    const content: string;
    export = content;
}

declare module "*.svg" {
    const content: string;
    export = content;
}

declare module "*.png" {}

declare function require(path: string): any;