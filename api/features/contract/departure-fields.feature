Feature: Departure fields
  In order to render the departure board
  As the frontend
  I want every departure to carry the fields I display

  Scenario: Every departure has the fields the board renders
    Given the current time is "2026-10-25 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
    And there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:05     | 12:07     | Łostowice |
      | brama-wyzynna-01 | 8    | 12:10     |           | Stogi     |
    When I request "/api/v1/test/departures" with query:
      | name   | value            |
      | stop[] | brama-wyzynna-01 |
    Then the response status should be 200
    And the response should be a JSON list of 2 items
    And every item should have the fields:
      | field     |
      | key       |
      | line      |
      | stop      |
      | display   |
      | estimated |
      | scheduled |
      | delay     |
    And every item's "line" should have the fields:
      | field  |
      | symbol |
      | type   |
      | night  |
      | fast   |
