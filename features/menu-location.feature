Feature: Manage FinPress menu locations

  Background:
    Given a FP install
    And I run `fp theme delete --all --force`
    And I run `fp theme install twentytwelve --activate`

  Scenario: Assign / remove location from a menu
    When I run `fp menu location list`
    Then STDOUT should be a table containing rows:
      | location       | description        |
      | primary        | Primary Menu       |

    When I run `fp menu create "Primary Menu"`
    And I run `fp menu location assign primary-menu primary`
    And I run `fp menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    | primary         |

    When I run `fp menu location list --format=ids`
    Then STDOUT should be:
      """
      primary
      """

    When I run `fp menu location remove primary-menu primary`
    And I run `fp menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    |                 |

    When I try `fp menu location assign secondary-menu secondary`
    Then STDERR should be:
      """
      Error: Invalid menu secondary-menu.
      """
    And the return code should be 1

    When I run `fp menu create "Secondary Menu"`
    And I try `fp menu location assign secondary-menu secondary`
    Then STDERR should be:
      """
      Error: Invalid location secondary.
      """
    And the return code should be 1

    When I run `fp menu location assign secondary-menu primary`
    Then STDOUT should be:
      """
      Success: Assigned location primary to menu secondary-menu.
      """

    When I run `fp menu list --fields=slug,locations`
    Then STDOUT should be a table containing rows:
      | slug            | locations       |
      | primary-menu    |                 |
      | secondary-menu  | primary         |
