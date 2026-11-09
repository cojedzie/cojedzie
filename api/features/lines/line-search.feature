Feature: Line search
  In order to open the timetable of a line I know by its number
  As a passenger
  I want to find lines by their symbol and look one up

  Background:
    Given there are lines:
      | symbol | type | night |
      | 111    | bus  | no    |
      | N1     | bus  | yes   |
      | 8      | tram | no    |
      | 10     | tram | no    |
      | 3      | tram | no    |

  Scenario: Lines are listed by symbol in natural order, night lines last
    When I list lines
    Then I should see lines "3, 8, 10, 111, N1"

  Scenario: A symbol finds the lines it begins
    When I search for lines with symbol "1"
    Then I should see lines "10, 111"

  Scenario: Symbol search ignores letter case
    When I search for lines with symbol "n"
    Then I should see lines "N1"

  Scenario: Search without a match finds nothing
    When I search for lines with symbol "7"
    Then I should see lines ""

  Scenario: Lines are listed by their ids
    When I list lines "8, 111"
    Then I should see lines "8, 111"

  Scenario: A line is looked up by its id
    When I look up line "N1"
    Then I should see line "N1" of type "bus" running at night
