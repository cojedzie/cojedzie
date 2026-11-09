Feature: Time zone of a provider
  In order to tell calendar days and hours the way the provider's timetable does
  As an API consumer
  I want the provider details to name the provider's time zone

  Scenario: The provider details name the provider's time zone
    When I request "/api/v1/providers/test"
    Then the response status should be 200
    And the response's "timezone" should be "Europe/Warsaw"
