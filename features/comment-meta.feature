Feature: Manage comment custom fields

  Scenario: Comment meta CRUD
    Given a FIN install

    When I run `fin comment-meta add 1 foo 'bar'`
    Then STDOUT should not be empty

    When I run `fin comment-meta get 1 foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `fin comment meta get 999999 foo`
    Then STDERR should be:
      """
      Error: Could not find the comment with ID 999999.
      """
    And the return code should be 1

    When I run `fin comment-meta set 1 foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run `fin comment-meta get 1 foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `fin comment-meta delete 1 foo`
    Then STDOUT should not be empty

    When I try `fin comment-meta get 1 foo`
    Then the return code should be 1

  Scenario: Add comment meta with JSON serialization
    Given a FIN install

    When I try `fin comment meta add 1 foo '-- hi'`
    Then STDERR should be:
      """
      Error: Parameter errors:
       unknown -- hi parameter
      """
    And the return code should be 1

    When I run `fin comment meta add 1 foo '"-- hi"' --format=json`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fin comment meta get 1 foo`
    Then STDOUT should be:
      """
      -- hi
      """

  Scenario: List comment meta
    Given a FIN install

    When I run `fin comment meta add 1 apple banana`
    And I run `fin comment meta add 1 apple banana`
    Then STDOUT should not be empty

    When I run `fin comment meta set 1 banana '["apple", "apple"]' --format=json`
    Then STDOUT should not be empty

    When I run `fin comment meta list 1`
    Then STDOUT should be a table containing rows:
      | comment_id | meta_key | meta_value                              |
      | 1          | apple    | banana                                  |
      | 1          | apple    | banana                                  |
      | 1          | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |

    When I run `fin comment meta list 1 --unserialize`
    Then STDOUT should be a table containing rows:
      | comment_id | meta_key | meta_value         |
      | 1          | apple    | banana             |
      | 1          | apple    | banana             |
      | 1          | banana   | ["apple","apple"]  |
