Feature: Unknown resource
  In order to tell a missing resource from an empty one
  As an API consumer
  I want a detail request for an unknown id to be rejected as not found

  Scenario Outline: Unknown id is not found
    When I request "/api/v1/test/<endpoint>"
    Then the response status should be 404

    Examples:
      | endpoint   |
      | stops/nope |
      | trips/nope |
