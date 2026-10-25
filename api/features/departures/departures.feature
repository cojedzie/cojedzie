Feature: Departures
  In order to know when my bus or tram leaves
  As a passenger
  I want to see upcoming departures from the stops I chose, with realtime estimates when there are any

  Background:
    Given the current time is "2026-10-25 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | brama-wyzynna-02 | Brama Wyżynna | 02      |
      | oliwa-01         | Oliwa         | 01      |

  Scenario: Departures of a stop are ordered by departure time
    Given there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:10     |           | Łostowice |
      | brama-wyzynna-01 | 12   | 12:02     |           | Oliwa     |
      | brama-wyzynna-01 | 8    | 12:05     |           | Stogi     |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 12   | brama-wyzynna-01 | 12:02     |           |       |
      | 8    | brama-wyzynna-01 | 12:05     |           |       |
      | 6    | brama-wyzynna-01 | 12:10     |           |       |

  Scenario: Departures of several stops are merged into one list
    Given there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:04     |           | Łostowice |
      | brama-wyzynna-02 | 12   | 12:02     |           | Oliwa     |
      | brama-wyzynna-02 | 8    | 12:06     |           | Stogi     |
      | oliwa-01         | 6    | 12:03     |           | Łostowice |
    When I ask for departures from "brama-wyzynna-01, brama-wyzynna-02"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 12   | brama-wyzynna-02 | 12:02     |           |       |
      | 6    | brama-wyzynna-01 | 12:04     |           |       |
      | 8    | brama-wyzynna-02 | 12:06     |           |       |

  Scenario: The number of departures can be limited
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 12:01     |           | A       |
      | brama-wyzynna-01 | 6    | 12:11     |           | A       |
      | brama-wyzynna-01 | 6    | 12:21     |           | A       |
      | brama-wyzynna-01 | 6    | 12:31     |           | A       |
    When I ask for at most 2 departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:01     |           |       |
      | 6    | brama-wyzynna-01 | 12:11     |           |       |

  Scenario: Departures that already left are not shown
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 11:58     |           | A       |
      | brama-wyzynna-01 | 8    | 11:58     | 12:03     | B       |
      | brama-wyzynna-01 | 12   | 12:01     | 11:59     | C       |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 8    | brama-wyzynna-01 | 11:58     | 12:03     | 300   |

  Scenario: Realtime estimates come with a delay in seconds
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 12:05     | 12:07     | late    |
      | brama-wyzynna-01 | 8    | 12:10     | 12:09     | early   |
      | brama-wyzynna-01 | 12   | 12:15     | 12:15     | on time |
      | brama-wyzynna-01 | 2    | 12:20     |           | planned |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:05     | 12:07     | 120   |
      | 8    | brama-wyzynna-01 | 12:10     | 12:09     | -60   |
      | 12   | brama-wyzynna-01 | 12:15     | 12:15     | 0     |
      | 2    | brama-wyzynna-01 | 12:20     |           |       |

  Scenario: Unknown stops are ignored
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 12:05     |           | A       |
    When I ask for departures from "brama-wyzynna-01, removed-stop-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:05     |           |       |

  Scenario: Asking for the same stop twice does not duplicate departures
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 12:05     |           | A       |
    When I ask for departures from "brama-wyzynna-01, brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:05     |           |       |

  Scenario: Only unknown stops give no departures
    When I ask for departures from "removed-stop-01"
    Then I should see no departures

  Scenario: Departures after midnight come after the ones before it
    Given the current time is "2026-10-25 23:55" in "Europe/Warsaw"
    And there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | N1   | 00:05     |           | A       |
      | brama-wyzynna-01 | N3   | 23:58     |           | B       |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | N3   | brama-wyzynna-01 | 23:58     |           |       |
      | N1   | brama-wyzynna-01 | 00:05     |           |       |

  Scenario: A live departure without a timetable entry is shown
    Given there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:10     |           | Łostowice |
    And there are live departures not in the timetable:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 12   | 12:05     | 12:06     | Oliwa   |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 12   | brama-wyzynna-01 | 12:05     | 12:06     | 60    |
      | 6    | brama-wyzynna-01 | 12:10     |           |       |

  Scenario: The destination comes from the timetable
    Given there are departures:
      | stop             | line | scheduled | estimated | display   | live display |
      | brama-wyzynna-01 | 6    | 12:05     | 12:07     | Łostowice | Lostowice    |
    When I ask for departures from "brama-wyzynna-01"
    Then the departures should go to:
      | line | display   |
      | 6    | Łostowice |

  Scenario: Timetable departures before the first live estimate are shown
    Given there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 12   | 12:02     |           | Oliwa     |
      | brama-wyzynna-01 | 6    | 12:05     | 12:06     | Łostowice |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 12   | brama-wyzynna-01 | 12:02     |           |       |
      | 6    | brama-wyzynna-01 | 12:05     | 12:06     | 60    |

  Scenario: The same line at the same time at two stops gives two departures
    Given there are departures:
      | stop             | line | scheduled | estimated | display   |
      | brama-wyzynna-01 | 6    | 12:05     |           | Łostowice |
      | brama-wyzynna-02 | 6    | 12:05     | 12:06     | Łostowice |
    When I ask for departures from "brama-wyzynna-01, brama-wyzynna-02"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:05     |           |       |
      | 6    | brama-wyzynna-02 | 12:05     | 12:06     | 60    |

  Scenario: Departures can be paged
    Given there are departures:
      | stop             | line | scheduled | estimated | display |
      | brama-wyzynna-01 | 6    | 12:01     |           | A       |
      | brama-wyzynna-01 | 6    | 12:11     |           | A       |
      | brama-wyzynna-01 | 6    | 12:21     |           | A       |
      | brama-wyzynna-01 | 6    | 12:31     |           | A       |
    When I ask for 2 departures from "brama-wyzynna-01" after skipping 1
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:11     |           |       |
      | 6    | brama-wyzynna-01 | 12:21     |           |       |
