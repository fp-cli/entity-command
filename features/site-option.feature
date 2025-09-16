Feature: Manage FinPress site options

  Scenario: Site Option CRUD
    Given a FIN multisite installation

    # String values
    When I run `fin site option add str_opt 'bar'`
    Then STDOUT should not be empty

    When I run `fin site option get str_opt`
    Then STDOUT should be:
      """
      bar
      """

    When I run `fin site option list`
    Then STDOUT should not be empty

    When I run `fin site option list`
    Then STDOUT should contain:
      """
      str_opt	bar
      """

    When I run `fin site option list --search='str_o*'`
    Then STDOUT should be a table containing rows:
      | meta_key     | meta_value    |
      | str_opt      | bar           |

    When I run `fin site option list --search='str_o*' --format=total_bytes`
    Then STDOUT should be:
      """
      3
      """

    When I run `fin site option list`
    Then STDOUT should contain:
      """
      admin_user_id	1
      """

    When I run `fin site option delete str_opt`
    Then STDOUT should not be empty

    When I run `fin site option list`
    Then STDOUT should not contain:
      """
      str_opt	bar
      """

    When I try `fin site option get str_opt`
    Then the return code should be 1

    # Integer values
    When I run `fin site option update admin_user_id 2`
    Then STDOUT should not be empty

    When I run `fin site option get admin_user_id`
    Then STDOUT should be:
      """
      2
      """

    When I run `fin site option update admin_user_id 1`
    Then STDOUT should contain:
      """
      Success: Updated 'admin_user_id' site option.
      """

    When I run the previous command again
    Then STDOUT should contain:
      """
      Success: Value passed for 'admin_user_id' site option is unchanged.
      """

    When I run `fin site option get admin_user_id`
    Then STDOUT should be:
      """
      1
      """

    # JSON values
    When I run `fin site option set json_opt '[ 1, 2 ]' --format=json`
    Then STDOUT should not be empty

    When I run the previous command again
    Then STDOUT should not be empty

    When I run `fin site option get json_opt --format=json`
    Then STDOUT should be:
      """
      [1,2]
      """

    # Reading from files
    Given a value.json file:
      """
      {
        "foo": "bar",
        "list": [1, 2, 3]
      }
      """
    When I run `fin site option set foo --format=json < value.json`
    And I run `fin site option get foo --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": "bar",
        "list": [1, 2, 3]
      }
      """

  Scenario: Error on single install
    Given a FIN installation

    When I try `fin site option get str_opt`
    Then STDERR should be:
      """
      Error: This is not a multisite installation.
      """
    And the return code should be 1

    When I try `fin site option add str_opt 'bar'`
    Then STDERR should be:
      """
      Error: This is not a multisite installation.
      """
    And the return code should be 1

  @require-mysql
  Scenario: Filter options by `--site_id`
    Given a FIN multisite installation

    When I run `fin db query "INSERT INTO fin_sitemeta (site_id,meta_key,meta_value) VALUES (2,'fin_cli_test_option','foobar');"`
    Then the return code should be 0

    When I run `fin site option list`
    Then STDOUT should contain:
      """
      fin_cli_test_option
      """
    And STDERR should be empty

    When I run `fin site option list --site_id=1`
    Then STDOUT should not contain:
      """
      fin_cli_test_option
      """
    And STDERR should be empty

    When I run `fin site option list --site_id=2`
    Then STDOUT should contain:
      """
      fin_cli_test_option
      """
    And STDERR should be empty
