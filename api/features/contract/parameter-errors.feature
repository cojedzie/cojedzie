Feature: Parameter errors
  In order to notice mistakes in my requests
  As an API consumer
  I want malformed parameters to be rejected as bad requests, never to fail the server

  Background:
    Given there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | oliwa-01         | Oliwa         | 01      |
    And line 6 runs through "brama-wyzynna-01, oliwa-01"

  Scenario Outline: Malformed limit or offset is a bad request
    When I request "/api/v1/test/<endpoint>" with query:
      | name    | value   |
      | <param> | <value> |
    Then the response status should be 400

    Examples:
      | endpoint                                  | param  | value |
      | departures?stop[]=brama-wyzynna-01        | limit  | abc   |
      | departures?stop[]=brama-wyzynna-01        | limit  | -1    |
      | departures?stop[]=brama-wyzynna-01        | offset | x     |
      | stops/brama-wyzynna-01/departures         | limit  | abc   |
      | stops/brama-wyzynna-01/departures         | limit  | -1    |
      | stops                                     | limit  | abc   |
      | stops                                     | limit  | -1    |
      | stops                                     | offset | x     |
      | stop-groups                               | limit  | abc   |
      | stop-groups                               | offset | -1    |
      | tracks                                    | limit  | abc   |
      | tracks                                    | offset | x     |
      | track-stops                               | limit  | abc   |
      | tracks/6-1/stops                          | limit  | -1    |
      | messages                                  | limit  | abc   |
      | messages                                  | offset | x     |

  Scenario Outline: A limit of zero gives an empty list
    When I request "/api/v1/test/<endpoint>" with query:
      | name  | value |
      | limit | 0     |
    Then the response status should be 200
    And the response should be a JSON list of 0 items

    Examples:
      | endpoint                           |
      | departures?stop[]=brama-wyzynna-01 |
      | stops                              |
      | stop-groups                        |
      | tracks                             |
      | track-stops                        |
      | messages                           |

  Scenario Outline: An unknown name operator is a bad request
    When I request "/api/v1/test/<endpoint>" with query:
      | name       | value |
      | name:bogus | x     |
    Then the response status should be 400

    Examples:
      | endpoint    |
      | stops       |
      | stop-groups |

  Scenario: Departures need at least one stop
    When I request "/api/v1/test/departures"
    Then the response status should be 400
