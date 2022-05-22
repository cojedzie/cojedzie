const dom   = require('xmldom');
const xpath = require('xpath');
const fs    = require('fs');

export default function({ match, exclude }) {
    return {
        name: 'svg-icon-loader',
        async transform(_, filename) {
            if (!match.test(filename)) {
                return {};
            }

            if (exclude && exclude.test(filename)) {
                return {};
            }

            const source = fs.readFileSync(filename, { encoding: 'utf-8' })

            const parser   = new dom.DOMParser();
            const svg      = parser.parseFromString(source, 'image/svg+xml');

            console.log(source);

            const result = xpath.useNamespaces({
                'svg': 'http://www.w3.org/2000/svg'
            })('string(//svg:path/@d)', svg);

            return {
                code: `export default "${result}";`
            };
        }
    }
}
