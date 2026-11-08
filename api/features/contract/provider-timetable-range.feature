Feature: Timetable range of a provider
  In order to offer only the days a timetable can be shown for
  As an API consumer
  I want the provider details to tell the first and the last service day of the provider's timetable

  Background:
    Given the current time is "2026-11-13 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |

  Scenario: The range spans the service days of the provider's trips
    Given there are timetable entries:
      | line | stop             | departure        | service day | display   |
      | 6    | brama-wyzynna-01 | 2026-11-13 00:30 | 2026-11-12  | Łostowice |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:00 | 2026-11-13  | Łostowice |
      | 6    | brama-wyzynna-01 | 2026-11-15 01:30 | 2026-11-14  | Łostowice |
    When I request "/api/v1/providers/test"
    Then the response status should be 200
    And the response's "timetable" should be exactly:
      | field | value      |
      | from  | 2026-11-12 |
      | to    | 2026-11-14 |

  Scenario: A provider without trips has no timetable range
    When I request "/api/v1/providers/test"
    Then the response status should be 200
    And the response's "timetable" should be "null"
