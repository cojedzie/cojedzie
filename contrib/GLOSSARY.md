# Co Jedzie

Realtime public transport departures: what leaves a stop now, and what is planned to leave it.

## Language

### Network

**Stop**:
One place where vehicles call, served in one direction, e.g. "Brama Wyżynna 01". Timetables and departures are
always about stops.
_Avoid_: Post, platform

**Stop group**:
Stops sharing a name, usually on both sides of a street or around one interchange. A way to find stops, not a place
vehicles call at.

**Track**:
The ordered stops one variant of a line runs through, ending at its destination. A line has several: one per
direction, plus shortened and depot runs.
_Avoid_: Route, variant

**Main track**:
The track of a line that riders know as the line in one direction, as opposed to its shortened or depot runs.
_Avoid_: Canonical track, default track

### Planned service

**Timetable**:
The planned service of a provider: which trips call at which stops at which times, for the days it is known.
_Avoid_: Schedule (that word means when a job runs, see ADR 0021)

**Timetable entry**:
One trip calling at one stop at a planned time.
_Avoid_: Scheduled stop

**Service day**:
The operating day a trip belongs to, as the provider assigns it. It can run past midnight, so a trip may depart
on the calendar day after its service day.
_Avoid_: Operating date, schedule day

### Real service

**Departure**:
A vehicle leaving a stop as it really happens or is estimated to: a timetable entry seen through live data, or a
live departure without one. Always about one stop.

**Departure history**:
The record of how departures really went, one last observation per departure per stop.
