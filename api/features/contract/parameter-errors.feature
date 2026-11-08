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
      | stops/brama-wyzynna-01/timetable          | limit  | abc   |
      | lines/6/timetable                         | offset | -1    |

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
      | stops/brama-wyzynna-01/timetable   |

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

  Scenario Outline: Invalid timetable time selections are bad requests
    When I request "/api/v1/test/<path>"
    Then the response status should be 400
    And the response's "detail" should contain "Invalid <parameter> parameter"

    Examples:
      | path                                                                                               | parameter |
      | stops/brama-wyzynna-01/timetable?day=weekly                                                        | day       |
      | stops/brama-wyzynna-01/timetable?date=13.11.2026                                                   | date      |
      | stops/brama-wyzynna-01/timetable?date=2026-02-30                                                   | date      |
      | stops/brama-wyzynna-01/timetable?date=2026-11-13&from=2026-11-13T10:00:00Z&to=2026-11-13T11:00:00Z | from      |
      | stops/brama-wyzynna-01/timetable?day=service&from=2026-11-13T10:00:00Z&to=2026-11-13T11:00:00Z     | from      |
      | stops/brama-wyzynna-01/timetable?from=2026-11-13T10:00:00Z                                         | to        |
      | stops/brama-wyzynna-01/timetable?to=2026-11-13T10:00:00Z                                           | from      |
      | stops/brama-wyzynna-01/timetable?from=2026-11-13T10:00:00Z&to=2026-11-13T10:00:00Z                 | to        |
      | stops/brama-wyzynna-01/timetable?from=yesterday&to=2026-11-13T11:00:00Z                            | from      |
      | stops/brama-wyzynna-01/timetable?from=2026-11-13T10:00:00&to=2026-11-13T11:00:00Z                  | from      |

  Scenario: A timetable needs at least one stop, line, track or trip
    When I request "/api/v1/test/timetable?date=2026-11-13"
    Then the response status should be 400
    And the response's "detail" should contain "Should specify at least one of stop, line, track, trip."

  Scenario Outline: A list where a single value is expected is a bad request
    When I request "/api/v1/test/<endpoint>"
    Then the response status should be 400

    Examples:
      | endpoint                                                                             |
      | stops?limit[]=1                                                                      |
      | stops?offset[]=1                                                                     |
      | stops?name[]=Oliwa                                                                   |
      | stops?name:contains[]=Oliwa                                                          |
      | stops?embed[]=destinations                                                           |
      | stop-groups?name[]=Oliwa                                                             |
      | tracks?limit[]=1                                                                     |
      | departures?stop[]=brama-wyzynna-01&limit[]=1                                         |
      | messages?limit[]=1                                                                   |
      | stops/brama-wyzynna-01/timetable?day[]=service                                       |
      | stops/brama-wyzynna-01/timetable?date[]=2026-11-13                                   |
      | stops/brama-wyzynna-01/timetable?from[]=2026-11-13T10:00:00Z&to=2026-11-13T11:00:00Z |

  Scenario Outline: Nested lists are a bad request
    When I request "/api/v1/test/<endpoint>"
    Then the response status should be 400

    Examples:
      | endpoint                             |
      | departures?stop[][]=brama-wyzynna-01 |
      | stops?id[][]=oliwa-01                |
      | tracks?stop[][]=oliwa-01             |
      | tracks?line[][]=6                    |
      | track-stops?stop[][]=oliwa-01        |

  Scenario Outline: Related ids can be sent as a list or comma-separated
    When I request "/api/v1/test/tracks?<query>"
    Then the response status should be 200
    And the response should be a JSON list of 1 items

    Examples:
      | query                      |
      | stop=oliwa-01              |
      | stop[]=oliwa-01            |
      | stop=oliwa-01,nope         |
      | stop[]=oliwa-01&stop[]=nope |

  Scenario: A malformed JSON body is a bad request
    When I send a "GET" request to "/api/v1/test/stops" with body:
      """
      {"name":
      """
    Then the response status should be 400

  Scenario: A JSON body that isn't an object is a bad request
    When I send a "GET" request to "/api/v1/test/stops" with body:
      """
      "Oliwa"
      """
    Then the response status should be 400
