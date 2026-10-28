# ZTM Gdańsk feeds, 2026-10-28

Recorded from the ZTM Gdańsk open data (the default URLs in `api/config/services.yaml`) on 2026-10-28, for
`tests/ZtmGdansk/DataImporter/ZtmGdanskRecordedFeedsTest`. Tests must not call upstream, so this is what they import.

- `agencies.json`: as published.
- `stops.json`, `routes.json`, `trips.json`, `stopsintrips.json`: only the `2026-10-28` day; upstream publishes ten
  days ahead in one file.
- `stopTimes/<routeId>.json`: the schedule of two routes on 2026-10-28 (`stopTimes?date=2026-10-28&routeId=…`), line
  740 (route 10741, agency 6) and line 723 (route 10723, agency 5). Upstream serves one file per route.

To record a newer day, download the same files, keep one day of the dated ones (they are keyed by date), pick a few
routes for `stopTimes`, and update the date in the test.
