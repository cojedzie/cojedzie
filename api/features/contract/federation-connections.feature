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

  Scenario Outline: A server connects only under its registered URL
    Given I authenticate as "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" with password "notarealsecretatall"
    When I send a "POST" request to "/api/v1/federation/connections" with body:
      """
      {"url": "<url>"}
      """
    Then the response status should be <status>

    Examples:
      | url                                | status |
      | http://federated:8080              | 201    |
      | http://federated:8080/             | 201    |
      | http://federated:8080/node-2       | 201    |
      | http://other:8080                  | 403    |
      | http://federated:80800             | 403    |
      | http://federated:8080.evil.example | 400    |
      | http://federated:8080@evil.example | 403    |
      |                                    | 400    |
      | not a url                          | 400    |
      | ftp://federated:8080               | 400    |

  Scenario: A missing URL is a bad request
    Given I authenticate as "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" with password "notarealsecretatall"
    When I send a "POST" request to "/api/v1/federation/connections" with body:
      """
      {}
      """
    Then the response status should be 400

  Scenario: A server closes its own connection
    Given the server "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" has an open connection "6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11" at "http://federated:8080"
    And I authenticate as "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" with password "notarealsecretatall"
    When I send a "DELETE" request to "/api/v1/federation/connections/6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11"
    Then the response status should be 200

  Scenario: A server cannot close another server's connection
    Given the server "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" has an open connection "6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11" at "http://federated:8080"
    And I authenticate as "0b6c4e1f-9a43-4a7e-8f0f-1d3c2b5a6e70" with password "othersecret"
    When I send a "DELETE" request to "/api/v1/federation/connections/6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11"
    Then the response status should be 403

  Scenario: Closing a connection needs credentials
    Given the server "a7cd192a-3dca-4fc8-b35d-91f2d6e10632" has an open connection "6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11" at "http://federated:8080"
    When I send a "DELETE" request to "/api/v1/federation/connections/6a1c3d3e-7f0a-4b8e-9d55-2f6b1c0e9a11"
    Then the response status should be 401
