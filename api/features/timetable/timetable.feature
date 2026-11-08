Feature: Timetable
  In order to plan a journey on another day or later today
  As a passenger
  I want to see the planned departures of a stop or a line, for a day or a range of time

  Background:
    Given the current time is "2026-11-13 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | oliwa-01         | Oliwa         | 01      |
    And there are timetable entries:
      | line | stop             | departure        | service day | display   |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  | Łostowice |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  | Łostowice |
      | 6    | brama-wyzynna-01 | 2026-11-14 00:40 | 2026-11-13  | Łostowice |
      | 6    | brama-wyzynna-01 | 2026-11-14 01:30 | 2026-11-14  | Łostowice |

  Scenario: A stop's calendar day
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value      |
      | date | 2026-11-13 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  |

  Scenario: A service day includes its night runs
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value      |
      | date | 2026-11-13 |
      | day  | service    |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  |
      | 6    | brama-wyzynna-01 | 2026-11-14 00:40 | 2026-11-13  |

  Scenario: An extended day adds the night runs to the calendar day
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value      |
      | date | 2026-11-13 |
      | day  | extended   |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  |
      | 6    | brama-wyzynna-01 | 2026-11-14 00:40 | 2026-11-13  |

  Scenario: Today is the default
    When I ask for the timetable of "timetable" with query:
      | name | value            |
      | stop | brama-wyzynna-01 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  |

  Scenario: A range matches departure times
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value                     |
      | from | 2026-11-13T23:00:00+01:00 |
      | to   | 2026-11-14T01:00:00+01:00 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-14 00:40 | 2026-11-13  |

  Scenario: Lines narrow a stop's timetable
    Given there are timetable entries:
      | line | stop             | departure        | display |
      | 8    | brama-wyzynna-01 | 2026-11-13 10:00 | Stogi   |
      | 8    | oliwa-01         | 2026-11-13 10:30 | Stogi   |
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value |
      | line | 8     |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 8    | brama-wyzynna-01 | 2026-11-13 10:00 | 2026-11-13  |
    When I ask for the timetable of "lines/8/timetable" with query:
      | name | value      |
      | date | 2026-11-13 |
    Then I should see timetable entries:
      | line | stop              | scheduled        | service day |
      | 8    | brama-wyzynna-01  | 2026-11-13 10:00 | 2026-11-13  |
      | 8    | destination-stogi | 2026-11-13 10:05 | 2026-11-13  |
      | 8    | oliwa-01          | 2026-11-13 10:30 | 2026-11-13  |
      | 8    | destination-stogi | 2026-11-13 10:35 | 2026-11-13  |

  Scenario: Tracks and trips narrow the timetable
    Given there are timetable entries:
      | line | stop             | departure        | display |
      | 8    | brama-wyzynna-01 | 2026-11-13 10:00 | Stogi   |
      | 8    | brama-wyzynna-01 | 2026-11-13 11:00 | Stogi   |
      | 8    | oliwa-01         | 2026-11-13 10:30 | Stogi   |
    When I ask for the timetable of "timetable" with query:
      | name  | value |
      | track | 8-2   |
    Then I should see timetable entries:
      | line | stop              | scheduled        | service day |
      | 8    | brama-wyzynna-01  | 2026-11-13 10:00 | 2026-11-13  |
      | 8    | destination-stogi | 2026-11-13 10:05 | 2026-11-13  |
      | 8    | brama-wyzynna-01  | 2026-11-13 11:00 | 2026-11-13  |
      | 8    | destination-stogi | 2026-11-13 11:05 | 2026-11-13  |
    When I ask for the timetable of "timetable" with query:
      | name | value  |
      | trip | trip-7 |
    Then I should see timetable entries:
      | line | stop              | scheduled        | service day |
      | 8    | oliwa-01          | 2026-11-13 10:30 | 2026-11-13  |
      | 8    | destination-stogi | 2026-11-13 10:35 | 2026-11-13  |
    When I ask for the timetable of "timetable" with query:
      | name  | value            |
      | track | 8-2              |
      | stop  | brama-wyzynna-01 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 8    | brama-wyzynna-01 | 2026-11-13 10:00 | 2026-11-13  |
      | 8    | brama-wyzynna-01 | 2026-11-13 11:00 | 2026-11-13  |

  Scenario: Route and query stops add up
    Given there are timetable entries:
      | line | stop     | departure        | display |
      | 8    | oliwa-01 | 2026-11-13 10:30 | Stogi   |
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value    |
      | stop | oliwa-01 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  |
      | 8    | oliwa-01         | 2026-11-13 10:30 | 2026-11-13  |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  |

  Scenario: A page holds at most 1000 entries and the offset pages through the rest
    Given line 6 leaves "oliwa-01" every 2 minutes from "05:00" to "23:58" on "2026-11-16"
    And line 8 leaves "oliwa-01" every 2 minutes from "05:00" to "23:58" on "2026-11-16"
    When I ask for the timetable of "stops/oliwa-01/timetable" with query:
      | name | value      |
      | date | 2026-11-16 |
    Then I should see 1000 timetable entries
    When I ask for the timetable of "stops/oliwa-01/timetable" with query:
      | name  | value      |
      | date  | 2026-11-16 |
      | limit | 5000       |
    Then I should see 1000 timetable entries
    When I ask for the timetable of "stops/oliwa-01/timetable" with query:
      | name   | value      |
      | date   | 2026-11-16 |
      | offset | 1000       |
    Then I should see 140 timetable entries
    And the first timetable entry should be line 6 at "2026-11-16 21:40"

  Scenario: A day that isn't stored is empty
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value      |
      | date | 2027-01-01 |
    Then I should see 0 timetable entries

  Scenario: A calendar day over a DST change
    Given the current time is "2026-10-25 12:00" in "Europe/Warsaw"
    And there are timetable entries:
      | line | stop     | departure        | display |
      | 8    | oliwa-01 | 2026-10-25 00:10 | Stogi   |
      | 8    | oliwa-01 | 2026-10-25 23:50 | Stogi   |
      | 8    | oliwa-01 | 2026-10-26 00:10 | Stogi   |
    When I ask for the timetable of "stops/oliwa-01/timetable" with query:
      | name | value      |
      | date | 2026-10-25 |
    Then I should see timetable entries:
      | line | stop     | scheduled        | service day |
      | 8    | oliwa-01 | 2026-10-25 00:10 | 2026-10-25  |
      | 8    | oliwa-01 | 2026-10-25 23:50 | 2026-10-25  |
