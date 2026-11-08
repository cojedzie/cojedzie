# 0029. Timetables are selected by calendar day or service day

- **Status:** Proposed
- **Date:** 2026-11-08
- **Applies to:** `api/src/Entity/TripEntity.php`, `api/src/Provider/*/DataImporter/**`, `api/migrations/**`,
  `api/src/Controller/Api/v1/*Timetable*`, `api/src/Filter/**`

## Context

The timetable API answers "what leaves this stop on Friday". A trip belongs to a **service day** that can run past
midnight: in ZTM Gdańsk a service day's trips start between about 01:10 and 01:08 local the next day, and the last
runs end around 03:20; service days don't overlap. A rider looking at a stop thinks in calendar days, but the night
buses after midnight belong to the evening before, and future day categories (weekday, Saturday, holiday) are a
property of the service day. The service date existed only as the `D{date}` suffix of ZTM trip ids.

## Considered options

1. **Calendar day only**: what departs between 00:00 and 24:00 local. Matches the stop, but splits a night
   service and can't support day categories.
2. **Service day only**: the provider's view. Hides that a 00:30 departure on Saturday belongs to Friday, and a
   rider asking for Saturday doesn't see it there.
3. **Both, chosen per request**, with the service day stored on the trip.
4. Parsing the service date from trip ids at query time: provider-specific and not indexable.

## Decision

We will store each trip's service day and let the client choose (option 3):

- `trip.service_date` is a not null, indexed column written by every importer of trips. The rollout migration
  backfills it from the dated ZTM trip ids.
- Timetable requests take `date` and `day`: `calendar` (default; departures at the stop on that local date),
  `service` (trips of that service day) or `extended` (both; the calendar day plus its service day's runs after
  midnight). `date` is in the provider's timezone.
- Every timetable entry carries its `serviceDay`, so clients can mark runs that belong to another day.
- Absolute ranges (`from`/`to`) always match the departure time at the stop and can't be combined with `day`.

## Consequences

- Clients decide how to present night services; `extended` repeats the hour after midnight in two consecutive
  days, told apart by `serviceDay`.
- Day categories can be added on top of the service day without changing the model.
- A new provider's importer has to know its trips' service day.

## Compliance

- Importers that write trips set `service_date`; nothing derives it from ids outside the migration.
- Service-day queries filter `trip.service_date`; calendar-day and range queries filter the departure time.
- Timetable entries expose `serviceDay`.
