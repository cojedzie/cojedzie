Feature: Track kinds
  In order to see only the runs I can take
  As a passenger
  I want to tell a line's main tracks from its shortened runs, and never see its depot runs

  Background:
    Given the current time is "2026-11-13 12:00" in "Europe/Warsaw"
    And there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | brama-oliwska-01 | Brama Oliwska | 01      |
      | oliwa-01         | Oliwa         | 01      |
      | zajezdnia-01     | Zajezdnia     | 01      |
    And there are tracks:
      | line | kind          | stops                                       |
      | 6    | main          | brama-wyzynna-01, brama-oliwska-01, oliwa-01 |
      | 6    | side          | brama-wyzynna-01, brama-oliwska-01           |
      | 6    | non-passenger | brama-wyzynna-01, zajezdnia-01               |

  Scenario: Tracks tell their kind, and depot runs are left out
    When I list tracks through stop "brama-wyzynna-01"
    Then I should see tracks:
      | line | kind | destination      |
      | 6    | main | oliwa-01         |
      | 6    | side | brama-oliwska-01 |

  Scenario: A depot run is not a destination
    When I look up stop "brama-wyzynna-01"
    Then stop "brama-wyzynna-01" should have destinations:
      | destination      | lines |
      | oliwa-01         | 6     |
      | brama-oliwska-01 | 6     |

  Scenario: Trips of a depot run are not in the timetable
    Given there are timetable entries:
      | line | stop             | departure        | display   | kind          |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:10 | Oliwa     | main          |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:20 | Zajezdnia | non-passenger |
    When I ask for the timetable of "stops/brama-wyzynna-01/timetable" with query:
      | name | value      |
      | date | 2026-11-13 |
    Then I should see timetable entries:
      | line | stop             | scheduled        | service day |
      | 6    | brama-wyzynna-01 | 2026-11-13 12:10 | 2026-11-13  |

  Scenario: Trips of a depot run are not departures
    Given there are departures:
      | stop             | line | scheduled | estimated | display   | kind          |
      | brama-wyzynna-01 | 6    | 12:10     |           | Oliwa     | main          |
      | brama-wyzynna-01 | 6    | 12:20     |           | Zajezdnia | non-passenger |
    When I ask for departures from "brama-wyzynna-01"
    Then I should see departures:
      | line | stop             | scheduled | estimated | delay |
      | 6    | brama-wyzynna-01 | 12:10     |           |       |
