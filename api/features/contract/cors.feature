Feature: Cross-origin requests
  In order to keep browsers from treating lookalike sites as the hub
  As the hub operator
  I want the hub to be allowed cross-origin by its exact origin only

  Scenario: The hub's origin is allowed
    When I send a "GET" request to "/api/v1/providers" with header "Origin": "https://cojedzie.pl"
    Then the response header "Access-Control-Allow-Origin" should be "https://cojedzie.pl"

  Scenario Outline: Lookalikes of the hub's origin are not allowed
    When I send a "GET" request to "/api/v1/providers" with header "Origin": "<origin>"
    Then the response should not have the header "Access-Control-Allow-Origin"

    Examples:
      | origin                                   |
      | https://cojedzie.pl.evil.example         |
      | https://cojedzieXpl                      |
      | https://evil.example?https://cojedzie.pl |
