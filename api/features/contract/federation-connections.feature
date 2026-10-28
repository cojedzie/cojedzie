Feature: Federation connections
  In order to keep the network to nodes I registered
  As the hub operator
  I want only registered servers holding their secret to open connections

  Background:
    Given a federated server "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" allowed at "http://federated:8080" with secret "notarealsecretatall"
    And a federated server "0b6c4e1f-9a43-4a7e-8f0f-1d3c2b5a6e70" allowed at "http://other:8080" with secret "othersecret"

  Scenario: A registered server connects with its secret
    Given I authenticate as "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" with password "notarealsecretatall"
    When I send a "POST" request to "/api/v1/federation/connections" with body:
      """
      {"url": "http://federated:8080"}
      """
    Then the response status should be 201

  Scenario Outline: Connecting without valid credentials is refused
    Given I authenticate as "<user>" with password "<password>"
    When I send a "POST" request to "/api/v1/federation/connections" with body:
      """
      {"url": "http://federated:8080"}
      """
    Then the response status should be 401
    And the response header "WWW-Authenticate" should be 'Basic realm="Co Jedzie federation"'
    And the response's "status" should be "401"

    Examples:
      | user                                 | password            |
      | a7cd192a-3dca-4fc8-b35d-91f2d6e10632 | wrong               |
      | 5f0c8f6e-1111-4c4c-9e9e-000000000000 | notarealsecretatall |
      | not-a-uuid                           | notarealsecretatall |

  Scenario: Connecting with no credentials is refused
    When I send a "POST" request to "/api/v1/federation/connections" with header "Authorization": "Bearer x"
    Then the response status should be 401

  Scenario: The connection belongs to the authenticated server, whatever the body says
    Given I authenticate as "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" with password "notarealsecretatall"
    When I send a "POST" request to "/api/v1/federation/connections" with body:
      """
      {"url": "http://federated:8080", "server_id": "0b6c4e1f-9a43-4a7e-8f0f-1d3c2b5a6e70"}
      """
    Then the response status should be 400
