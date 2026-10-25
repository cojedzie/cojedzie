Feature: Filter values
  In order to get the same results however the value is typed
  As an API consumer
  I want whitespace around filter values to be ignored on every endpoint with field filters

  Background:
    Given there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | oliwa-01         | Oliwa         | 01      |
    And line 6 runs through "brama-wyzynna-01, oliwa-01"

  Scenario Outline: Whitespace around a filter value is ignored
    When I request "/api/v1/test/<endpoint>"
    Then the response status should be 200
    And the response should be a JSON list of <count> items

    Examples:
      | endpoint                      | count |
      | stops?name=%20Oliwa%20        | 1     |
      | stop-groups?name=%20%20Oliwa  | 1     |
      | stops?name:begins=%20Oli      | 1     |
      | track-stops?order=%201%20     | 1     |
