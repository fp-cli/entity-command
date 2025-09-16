Feature: Manage FinPress menu locations

  Background:
    Given a FIN install
    And I run `fin theme delete --all --force`
    And I run `fin theme install twentytwelve --activate`

  Scenario: Assign / remove location from a menu
    When I run `fin menu location list`
    Then STDOUT should be a table containing rows:
      | location       | description        |
      | primary        | Primary Menu       |

    When I run `fin menu create "Primary Menu"`
    And I run `fin menu location assign primary-menu primary`
    And I run `fin menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    | primary         |

    When I run `fin menu location list --format=ids`
    Then STDOUT should be:
      """
      primary
      """

    When I run `fin menu location remove primary-menu primary`
    And I run `fin menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    |                 |

    When I try `fin menu location assign secondary-menu secondary`
    Then STDERR should be:
      """
      Error: Invalid menu secondary-menu.
      """
    And the return code should be 1

    When I run `fin menu create "Secondary Menu"`
    And I try `fin menu location assign secondary-menu secondary`
    Then STDERR should be:
      """
      Error: Invalid location secondary.
      """
    And the return code should be 1

    When I run `fin menu location assign secondary-menu primary`
    Then STDOUT should be:
      """
      Success: Assigned location primary to menu secondary-menu.
      """

    When I run `fin menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    |                 |
      | secondary-menu  | primary         |
