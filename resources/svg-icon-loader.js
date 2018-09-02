const dom   = require('xmldom');
const xpath = require('xpath');

module.exports = function svgIconLoader(source) {
    const parser   = new dom.DOMParser();
    const svg      = parser.parseFromString(source, 'image/svg+xml');

    const result = xpath.useNamespaces({
        'svg': 'http://www.w3.org/2000/svg'
    })('string(//svg:path/@d)', svg);

    return result;
};