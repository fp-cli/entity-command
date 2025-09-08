Feature: Manage signups in a multisite installation

  Scenario: Not applicable in single installation site
    Given a FP install

    When I try `fp user signup list`
    Then STDERR should be:
      """
      Error: This is not a multisite installation.
      """

  Scenario: List signups
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`
    And I run `fp eval 'fpmu_signup_user( "johnuser", "johnuser@example.com" );'`

    When I run `fp user signup list --fields=signup_id,user_login,user_email,active --format=csv`
    Then STDOUT should be:
      """
      signup_id,user_login,user_email,active
      1,bobuser,bobuser@example.com,0
      2,johnuser,johnuser@example.com,0
      """

    When I run `fp user signup list --format=count --active=1`
    Then STDOUT should be:
      """
      0
      """

    When I run `fp user signup activate bobuser`
    Then STDOUT should contain:
      """
      Success: Activated 1 of 1 signups.
      """

    When I run `fp user signup list --fields=signup_id,user_login,user_email,active --format=csv --active=1`
    Then STDOUT should be:
      """
      signup_id,user_login,user_email,active
      1,bobuser,bobuser@example.com,1
      """

    When I run `fp user signup list --fields=signup_id,user_login,user_email,active --format=csv --per_page=1`
    Then STDOUT should be:
      """
      signup_id,user_login,user_email,active
      1,bobuser,bobuser@example.com,1
      """

  Scenario: Get signup
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`

    When I run `fp user signup get 1 --field=user_login`
    Then STDOUT should be:
      """
      bobuser
      """

    When I run `fp user signup get bobuser --fields=signup_id,user_login,user_email,active --format=csv`
    Then STDOUT should be:
      """
      signup_id,user_login,user_email,active
      1,bobuser,bobuser@example.com,0
      """

  Scenario: Activate signup
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`

    When I run `fp user signup get bobuser --field=active`
    Then STDOUT should be:
      """
      0
      """

    When I run `fp user signup activate bobuser`
    Then STDOUT should contain:
      """
      Success: Activated 1 of 1 signups.
      """

    When I try the previous command again
    Then STDERR should contain:
      """
      Warning: Failed activating signup 1.
      """

    When I run `fp user signup get bobuser --field=active`
    Then STDOUT should be:
      """
      1
      """

    When I run `fp user get bobuser --field=user_email`
    Then STDOUT should be:
      """
      bobuser@example.com
      """

  Scenario: Activate multiple signups
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`
    And I run `fp eval 'fpmu_signup_user( "johnuser", "johnuser@example.com" );'`

    When I run `fp user signup list --active=0 --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `fp user signup activate bobuser johnuser`
    Then STDOUT should contain:
      """
      Success: Activated 2 of 2 signups.
      """

    When I run `fp user signup list --active=1 --format=count`
    Then STDOUT should be:
      """
      2
      """

  Scenario: Activate blog signup entry
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_blog( "example.com", "/bobsite/", "My Awesome Title", "bobuser", "bobuser@example.com" );'`

    When I run `fp user signup get bobuser --fields=user_login,domain,path,active --format=csv`
    Then STDOUT should be:
      """
      user_login,domain,path,active
      bobuser,example.com,/bobsite/,0
      """

    When I run `fp user signup activate bobuser`
    Then STDOUT should contain:
      """
      Success: Activated 1 of 1 signups.
      """

    When I run `fp site list --fields=domain,path`
    Then STDOUT should be a table containing rows:
      | domain      | path      |
      | example.com | /         |
      | example.com | /bobsite/ |

  Scenario: Delete signups
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`
    And I run `fp eval 'fpmu_signup_user( "johnuser", "johnuser@example.com" );'`

    When I run `fp user signup get bobuser --field=user_email`
    Then STDOUT should be:
      """
      bobuser@example.com
      """

    When I run `fp user signup get johnuser --field=user_email`
    Then STDOUT should be:
      """
      johnuser@example.com
      """

    When I run `fp user signup delete bobuser@example.com johnuser@example.com`
    Then STDOUT should contain:
      """
      Success: Deleted 2 of 2 signups.
      """

    When I try `fp user signup get bobuser`
    Then STDERR should be:
      """
      Error: Invalid signup ID, email, login, or activation key: 'bobuser'
      """

  Scenario: Delete all signups
    Given a FP multisite install
    And I run `fp eval 'fpmu_signup_user( "bobuser", "bobuser@example.com" );'`
    And I run `fp eval 'fpmu_signup_user( "johnuser", "johnuser@example.com" );'`

    When I try `fp user signup delete`
    Then STDERR should be:
      """
      Error: You need to specify either one or more signups or provide the --all flag.
      """

    When I run `fp user signup delete --all`
    Then STDOUT should contain:
      """
      Success: Deleted all signups.
      """

    When I run `fp user signup list --format=count`
    Then STDOUT should be:
      """
      0
      """
