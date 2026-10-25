Feature: Unknown provider
  In order to notice a wrong provider in my request
  As an API consumer
  I want requests for an unknown provider to be rejected as not found

  Scenario Outline: Unknown provider is not found
    When I request "/api/v1/nope/<endpoint>"
    Then the response status should be 404

    Examples:
      | endpoint    |
      | departures  |
      | stop-groups |
      | stops       |

  Scenario: An unknown provider is reported even when the query is malformed too
    When I request "/api/v1/nope/stops?limit=abc"
    Then the response status should be 404
