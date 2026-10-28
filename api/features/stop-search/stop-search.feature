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

  Scenario: Groups are listed by name
    Given there are stops:
      | id   | name             | variant |
      | 1010 | Wrzeszcz PKM     | 01      |
      | 2020 | Wrzeszcz Kościół | 01      |
    When I search for stops named "Wrzeszcz"
    Then I should see stop groups:
      | name             | stops |
      | Wrzeszcz Kościół | 2020  |
      | Wrzeszcz PKM     | 1010  |

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

  Scenario: Listed stops come with their destinations when asked
    When I list stops named "Brama Wyżynna" with destinations
    Then stop "brama-wyzynna-01" should have destinations:
      | destination | lines |
      | oliwa-01    | 6     |
    And stop "brama-wyzynna-02" should have no destinations

  Scenario: A stop looked up by id comes with its destinations
    When I look up stop "brama-wyzynna-01"
    Then stop "brama-wyzynna-01" should have destinations:
      | destination | lines |
      | oliwa-01    | 6     |

  Scenario: Search by the beginning of a name
    When I search for stops whose name begins with "Brama"
    Then I should see stop groups:
      | name          | stops                              |
      | Brama Oliwska | brama-oliwska-01                   |
      | Brama Wyżynna | brama-wyzynna-01, brama-wyzynna-02 |

  Scenario: The beginning of a name must match its start
    When I search for stops whose name begins with "Wyżynna"
    Then I should see no stop groups

  Scenario Outline: Search ignores the case of Polish letters
    When I search for stops named "<term>"
    Then I should see stop groups:
      | name     | stops       |
      | Żabianka | zabianka-01 |

    Examples:
      | term     |
      | żabianka |
      | ŻABIANKA |
      | Żabianka |

  Scenario: A lowercase Polish first letter finds the stop
    Given there are stops:
      | id           | name      | variant |
      | lostowice-01 | Łostowice | 01      |
    When I search for stops whose name begins with "łost"
    Then I should see stop groups:
      | name      | stops        |
      | Łostowice | lostowice-01 |

  Scenario Outline: Wildcard characters are searched for literally
    When I search for stops named "<term>"
    Then I should see no stop groups

    Examples:
      | term |
      | %    |
      | _    |

  Scenario: A name containing a wildcard character can be found
    Given there are stops:
      | id             | name         | variant |
      | sto-procent-01 | 100% Kultury | 01      |
    When I search for stops named "100%"
    Then I should see stop groups:
      | name         | stops          |
      | 100% Kultury | sto-procent-01 |
