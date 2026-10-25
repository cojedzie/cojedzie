Feature: Stop search
  In order to find the stops I want departures for
  As a passenger
  I want to search stops by name and see them grouped with all their variants

  Background:
    Given there are stops:
      | id               | name          | variant |
      | brama-wyzynna-01 | Brama Wyżynna | 01      |
      | brama-wyzynna-02 | Brama Wyżynna | 02      |
      | brama-oliwska-01 | Brama Oliwska | 01      |
      | zabianka-01      | Żabianka      | 01      |
      | oliwa-01         | Oliwa         | 01      |
    And line 6 runs through "brama-wyzynna-01, brama-oliwska-01, oliwa-01"

  Scenario: Part of a name finds matching groups with all their stops
    When I search for stops named "Brama"
    Then I should see stop groups:
      | name          | stops                              |
      | Brama Oliwska | brama-oliwska-01                   |
      | Brama Wyżynna | brama-wyzynna-01, brama-wyzynna-02 |

  Scenario Outline: Search ignores letter case
    When I search for stops named "<term>"
    Then I should see stop groups:
      | name          | stops                              |
      | Brama Wyżynna | brama-wyzynna-01, brama-wyzynna-02 |

    Examples:
      | term          |
      | brama wyżynna |
      | BRAMA WYŻYNNA |

  Scenario: Search without a match finds nothing
    When I search for stops named "Kraków"
    Then I should see no stop groups

  @wip
  Scenario: Search term is trimmed
    When I search for stops named " Oliwa "
    Then I should see stop groups:
      | name  | stops    |
      | Oliwa | oliwa-01 |

  Scenario: Destinations come with the lines going there
    When I search for stops named "Brama Wyżynna" with destinations
    Then stop "brama-wyzynna-01" should have destinations:
      | destination | lines |
      | oliwa-01    | 6     |
    And stop "brama-wyzynna-02" should have no destinations

  Scenario: Search by the beginning of a name
    When I search for stops whose name begins with "Brama"
    Then I should see stop groups:
      | name          | stops                              |
      | Brama Oliwska | brama-oliwska-01                   |
      | Brama Wyżynna | brama-wyzynna-01, brama-wyzynna-02 |

  Scenario: The beginning of a name must match its start
    When I search for stops whose name begins with "Wyżynna"
    Then I should see no stop groups
