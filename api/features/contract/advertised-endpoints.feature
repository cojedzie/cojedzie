Feature: Advertised endpoints
  In order to send each request to a node that supports it
  As the frontend or a federation hub
  I want every node to list its endpoints with their versions

  Scenario: The node lists its endpoints
    When I request "/api/v1/status/endpoints"
    Then the response status should be 200
    And the response should be a non-empty JSON list
    And every item should have the fields:
      | field    |
      | name     |
      | template |
      | version  |
      | methods  |

  Scenario: The node status carries the same endpoint list
    When I request "/api/v1/status"
    Then the response status should be 200
    And the response's "endpoints" should be a non-empty JSON list
