Feature: Nested references
  In order to keep responses small
  As an API client
  I want related objects I didn't ask for to come back as references holding only their id

  Background:
    Given the current time is "2026-10-25 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
    And there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:05     |           | Łostowice |

  Scenario: A relation the DTO declares as a reference has only its id, even when it is loaded
    When I request "/api/v1/test/departures" with query:
      | name   | value            |
      | stop[] | brama-wyzynna-01 |
    Then the response status should be 200
    And the response should be a JSON list of 1 items
    And every item's "line" should have the fields:
      | field  |
      | id     |
      | symbol |
      | type   |
    And the response's "0.track" should be exactly:
      | field | value              |
      | id    | 6-1                |
      | $type | vnd.cojedzie.track |
    And the response's "0.trip" should be exactly:
      | field | value             |
      | id    | trip-1            |
      | $type | vnd.cojedzie.trip |

  Scenario: A relation the query didn't load has only its id
    When I request "/api/v1/test/trips/trip-1"
    Then the response status should be 200
    And the response's "track" should be exactly:
      | field | value              |
      | id    | 6-1                |
      | $type | vnd.cojedzie.track |
    And the response's "schedule.0.track" should be exactly:
      | field | value              |
      | id    | 6-1                |
      | $type | vnd.cojedzie.track |
    And the response's "schedule.1.track" should be exactly:
      | field | value              |
      | id    | 6-1                |
      | $type | vnd.cojedzie.track |
