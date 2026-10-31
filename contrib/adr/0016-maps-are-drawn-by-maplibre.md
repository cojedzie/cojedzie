# 0016. Maps are drawn by MapLibre

- **Status:** Accepted
- **Date:** 2026-10-31
- **Applies to:** `front/src/components/map.ts`, `front/src/components/ui/UiMap.vue`, `front/package.json`

## Context

Maps are Leaflet (`@vue-leaflet/vue-leaflet`) with a vector base layer: a MapTiler style drawn by WebGL through a
Leaflet plugin. The renderer was `mapbox-gl` 1.13 with `mapbox-gl-leaflet`, pinned there because every later
release of `mapbox-gl` (2.x, 3.x) is under Mapbox's proprietary licence, needs a Mapbox access token and bills map
loads. The style and tiles come from MapTiler, not Mapbox, and 1.13 is no longer maintained.

## Considered options

1. **Stay on `mapbox-gl` 1.13.** Nothing to change, but unmaintained, with no fixes for new browsers.
2. **Upgrade to `mapbox-gl` 3.** Maintained, but proprietary, and it needs a Mapbox account and token for a style
   we take from MapTiler.
3. **MapLibre GL JS** (chosen). The BSD-licensed fork of `mapbox-gl` 1.x, maintained, works with MapTiler styles,
   with an official Leaflet plugin (`@maplibre/maplibre-gl-leaflet`).

## Decision

We will draw vector maps with `maplibre-gl` through `@maplibre/maplibre-gl-leaflet`, keeping Leaflet and
`@vue-leaflet/vue-leaflet` for the map, markers and controls.

## Consequences

- **Open licence, no token.** The map needs only the MapTiler key it already had.
- **Worker setup.** MapLibre loads its web worker from next to its own module; with Vite's dependency pre-bundling
  that path does not exist, so `map.ts` sets the worker URL explicitly.
- **Attribution.** The plugin reads it from MapLibre's `attributionControl.customAttribution`, not from Leaflet's
  `attribution` option.

## Compliance

- **No Mapbox.** There is no `mapbox-gl` or `mapbox-gl-leaflet` package in `front/package.json`.
- **One renderer.** Vector layers are created through `@maplibre/maplibre-gl-leaflet` in
  `front/src/components/map.ts`; components use `UiMap`, not MapLibre directly.
