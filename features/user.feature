Feature: Manage FinPress users

  Scenario: User CRUD operations
    Given a FP install

    When I try `fp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `fp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=author --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `fp user get {USER_ID}`
    Then STDOUT should be a table containing rows:
      | Field        | Value      |
      | ID           | {USER_ID}  |
      | roles        | author     |

    When I run `fp user exists {USER_ID}`
    Then STDOUT should be:
      """
      Success: User with ID {USER_ID} exists.
      """
    And the return code should be 0

    When I try `fp user exists 1000`
    Then STDOUT should be empty
    And the return code should be 1

    When I run `fp user get {USER_ID} --field=user_registered`
    Then STDOUT should not contain:
      """
      0000-00-00 00:00:00
      """

    When I run `fp user meta get {USER_ID} first_name`
    Then STDOUT should be:
      """
      test
      """

    When I run `fp user list --fields=user_login,roles`
    Then STDOUT should be a table containing rows:
      | user_login        | roles      |
      | testuser2         | author     |

    When I run `fp user meta get {USER_ID} last_name`
    Then STDOUT should be:
      """
      user
      """

    When I run `fp user delete {USER_ID} --yes`
    Then STDOUT should not be empty

    When I try `fp user create testuser2 testuser2@example.com --role=wrongrole --porcelain`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `fp user create testuser testuser@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I try the previous command again
    Then the return code should be 1

    When I run `fp user update {USER_ID} --display_name=Foo`
    And I run `fp user get {USER_ID}`
    Then STDOUT should be a table containing rows:
      | Field        | Value     |
      | ID           | {USER_ID} |
      | display_name | Foo       |

    When I run `fp user get testuser@example.com`
    Then STDOUT should be a table containing rows:
      | Field        | Value     |
      | ID           | {USER_ID} |
      | display_name | Foo       |

    When I run `fp user delete {USER_ID} --yes`
    Then STDOUT should not be empty

    When I run `fp user create testuser3 testuser3@example.com --user_pass=testuser3pass`
    Then STDOUT should not contain:
      """
      Password:
      """

    # Check with valid password.
    When I run `fp user check-password testuser3 testuser3pass`
    Then the return code should be 0

    # Check with invalid password.
    When I try `fp user check-password testuser3 invalidpass`
    Then the return code should be 1

    When I try `fp user check-password invaliduser randomstring`
    Then STDERR should contain:
      """
      Invalid user ID, email or login: 'invaliduser'
      """
    And the return code should be 1

    When I run `fp user create testuser3b testuser3b@example.com --user_pass="test\"user3b's\pass\!"`
    Then STDOUT should not contain:
      """
      Password:
      """

    # Check password without the `--escape-chars` option.
    When I try `fp user check-password testuser3b "test\"user3b's\pass\!"`
    Then STDERR should be:
      """
      Warning: Password contains characters that need to be escaped. Please escape them manually or use the `--escape-chars` option.
      """
    And the return code should be 1

    # Check password with the `--escape-chars` option.
    When I try `fp user check-password testuser3b "test\"user3b's\pass\!" --escape-chars`
    Then the return code should be 0

    # Check password with manually escaped characters.
    When I try `fp user check-password testuser3b "test\\\"user3b\'s\\\pass\\\!"`
    Then the return code should be 0

  Scenario: Reassigning user posts
    Given a FP multisite install

    When I run `fp user create bobjones bob@example.com --role=author --porcelain`
    Then save STDOUT as {BOB_ID}

    When I run `fp user create sally sally@example.com --role=editor --porcelain`
    Then save STDOUT as {SALLY_ID}

    When I run `fp post generate --count=3 --post_author=bobjones`
    And I run `fp post list --author={BOB_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `fp user delete bobjones --reassign={SALLY_ID}`
    And I run `fp post list --author={SALLY_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I try `fp user update 9999 --user_pass=securepassword`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: No valid users found.
      """

  Scenario: Delete user with invalid reassign
    Given a FP install
    And a session_no file:
      """
      n
      """
    And a session_yes file:
      """
      y
      """

    When I run `fp user create bobjones bob@example.com --role=author --porcelain`
    Then save STDOUT as {BOB_ID}

    When I run `fp post list --format=count`
    Then save STDOUT as {TOTAL_POSTS}

    When I run `fp post generate --count=3 --format=ids --post_author=bobjones`
    And I run `fp post list --author={BOB_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `fp user delete bobjones < session_no`
    Then STDOUT should contain:
      """
      --reassign parameter not passed. All associated posts will be deleted. Proceed? [y/n]
      """

    When I run `fp user delete bobjones --reassign=99999 < session_no`
    Then STDOUT should contain:
      """
      --reassign parameter is invalid. All associated posts will be deleted. Proceed? [y/n]
      """

    When I run `fp user delete bobjones < session_yes`
    And I run `fp post list --format=count`
    Then STDOUT should be:
      """
      {TOTAL_POSTS}
      """

  Scenario: Deleting user from the whole network
    Given a FP multisite install

    When I run `fp user create bobjones bob@example.com --role=author --porcelain`
    Then save STDOUT as {BOB_ID}

    When I run `fp user get bobjones`
    Then STDOUT should not be empty

    When I run `fp user delete bobjones --network --yes`
    Then STDOUT should not be empty

    When I try `fp user get bobjones`
    Then STDERR should not be empty
    And the return code should be 1

  Scenario: Trying to delete existing user with no roles from a subsite
    Given a FP multisite install

    When I run `fp user create bobjones bob@example.com --role=author --url=https://example.com --porcelain`
    Then save STDOUT as {BOB_ID}

    When I run `fp user delete bobjones --yes`
    Then STDOUT should contain:
      """
      Success: Removed user
      """
    And STDERR should be empty

    When I try `fp user delete bobjones --yes`
    Then STDERR should be:
      """
      Warning: No roles found for user {BOB_ID} on https://example.com, no users deleted.
      """
    And the return code should be 1

  @require-fp-4.0
  Scenario: Trying to delete super admin
    Given a FP multisite install

    When I run `fp user create bobjones bob@example.com --role=author --porcelain`
    Then save STDOUT as {BOB_ID}

    When I run `fp super-admin add {BOB_ID}`
    And I try `fp user delete bobjones --network --yes`
    Then STDERR should be:
      """
      Warning: Failed deleting user {BOB_ID}. The user is a super admin.
      """
    And the return code should be 1

  Scenario: Create new users on multisite
    Given a FP multisite install

    When I try `fp user create bob-jones bobjones@example.com`
    Then STDERR should contain:
      """
      lowercase letters (a-z) and numbers
      """
    And the return code should be 1

    When I run `fp user create bobjones bobjones@example.com --display_name="Bob Jones"`
    Then STDOUT should not be empty

    When I run `fp user get bobjones --field=display_name`
    Then STDOUT should be:
      """
      Bob Jones
      """

  Scenario: Managing user roles
    Given a FP install

    When I try `fp user add-role 1`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify at least one role to add.
      """
    And STDOUT should be empty

    When I run `fp user add-role 1 editor`
    Then STDOUT should be:
      """
      Success: Added 'editor' role for admin (1).
      """

    When I run `fp user get 1 --field=roles`
    Then STDOUT should be:
      """
      administrator, editor
      """

    When I run `fp user add-role 1 editor contributor`
    Then STDOUT should be:
      """
      Success: Added 'editor', 'contributor' roles for admin (1).
      """

    When I run `fp user get 1 --field=roles`
    Then STDOUT should be:
      """
      administrator, editor, contributor
      """

    When I run `fp user remove-role 1 editor contributor`
    Then STDOUT should be:
      """
      Success: Removed 'editor', 'contributor' roles from admin (1).
      """

    When I run `fp user get 1 --field=roles`
    Then STDOUT should be:
      """
      administrator
      """

    When I try `fp user add-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I try `fp user set-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I try `fp user remove-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I run `fp user set-role 1 author`
    Then STDOUT should not be empty

    When I run `fp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value  |
      | roles | author |

    When I run `fp user remove-role 1 editor`
    Then STDOUT should not be empty

    When I run `fp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value  |
      | roles | author |

    When I run `fp user remove-role 1`
    Then STDOUT should not be empty

    When I run `fp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value |
      | roles |       |

  Scenario: Invalid User Role
    Given a FP install
    When I run `fp user create testuser4 testemail4@example.com`
    And I try `fp user update testuser4 --role=banana`
    Then STDERR should be:
      """
      Warning: Role doesn't exist: banana
      """
    And STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0

    When I run `fp user get admin --field=roles`
    Then STDOUT should be:
      """
      administrator
      """

  Scenario: Managing user capabilities
    Given a FP install

    When I run `fp user add-cap 1 edit_vip_product`
    Then STDOUT should be:
      """
      Success: Added 'edit_vip_product' capability for admin (1).
      """

    When I run `fp user list-caps 1 | tail -n 1`
    Then STDOUT should be:
      """
      edit_vip_product
      """

    When I run `fp user remove-cap 1 edit_vip_product`
    Then STDOUT should be:
      """
      Success: Removed 'edit_vip_product' cap for admin (1).
      """

    When I try the previous command again
    Then the return code should be 1
    And STDERR should be:
      """
      Error: No such 'edit_vip_product' cap for admin (1).
      """
    And STDOUT should be empty

    When I run `fp user list-caps 1`
    Then STDOUT should not contain:
      """
      edit_vip_product
      """
    And STDOUT should contain:
      """
      publish_posts
      """

    When I try `fp user remove-cap 1 publish_posts`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'publish_posts' cap for admin (1) is inherited from a role.
      """
    And STDOUT should be empty

    When I run `fp user list-caps 1`
    Then STDOUT should contain:
      """
      publish_posts
      """

  Scenario: Show error when trying to remove capability same as role
    Given a FP install

    When I run `fp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=contributor --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `fp user list-caps {USER_ID}`
    Then STDOUT should contain:
      """
      contributor
      """

    When I run `fp user get {USER_ID} --field=roles`
    Then STDOUT should contain:
      """
      contributor
      """

    When I try `fp user remove-cap {USER_ID} contributor`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Aborting because a role has the same name as 'contributor'. Use `fp user remove-cap {USER_ID} contributor --force` to proceed with the removal.
      """
    And STDOUT should be empty

    When I run `fp user remove-cap {USER_ID} contributor --force`
    Then STDOUT should be:
      """
      Success: Removed 'contributor' cap for testuser2 ({USER_ID}).
      """

  Scenario: Show password when creating a user
    Given a FP install

    When I run `fp user create testrandompass testrandompass@example.com`
    Then STDOUT should contain:
      """
      Password:
      """

    When I run `fp user create testsuppliedpass testsuppliedpass@example.com --user_pass=suppliedpass`
    Then STDOUT should not contain:
      """
      Password:
      """

  Scenario: List network users
    Given a FP multisite install

    When I run `fp user create testsubscriber testsubscriber@example.com`
    Then STDOUT should contain:
      """
      Success: Created user
      """

    When I run `fp user list --field=user_login`
    Then STDOUT should contain:
      """
      testsubscriber
      """

    When I run `fp user delete testsubscriber --yes`
    Then STDOUT should contain:
      """
      Success: Removed user
      """

    When I run `fp user list --field=user_login`
    Then STDOUT should not contain:
      """
      testsubscriber
      """

    When I run `fp user list --field=user_login --network`
    Then STDOUT should contain:
      """
      testsubscriber
      """

  Scenario: Listing user capabilities
    Given a FP install

    When I run `fp user create bob bob@gmail.com --role=contributor`
    And I run `fp user list-caps bob`
    Then STDOUT should be:
      """
      edit_posts
      read
      level_1
      level_0
      delete_posts
      contributor
      """

    When I run `fp user list-caps bob --format=json`
    Then STDOUT should be:
      """
      [{"name":"edit_posts"},{"name":"read"},{"name":"level_1"},{"name":"level_0"},{"name":"delete_posts"},{"name":"contributor"}]
      """

    When I run `fp user list-caps bob --format=count`
    Then STDOUT should be:
      """
      6
      """

    When I run `fp user list-caps bob --exclude-role-names`
    Then STDOUT should be:
      """
      edit_posts
      read
      level_1
      level_0
      delete_posts
      """

    When I run `fp user add-cap bob newcap`
    And I run `fp user list-caps bob --origin=role`
    Then STDOUT should be:
      """
      edit_posts
      read
      level_1
      level_0
      delete_posts
      contributor
      """

    When I run `fp user list-caps bob --origin=user`
    Then STDOUT should be:
      """
      newcap
      """

  Scenario: Make sure FinPress receives the slashed data it expects
    Given a FP install

    When I run `fp user create slasheduser slasheduser@example.com --display_name='My\User' --porcelain`
    Then save STDOUT as {USER_ID}

    When I run `fp user get {USER_ID} --field=display_name`
    Then STDOUT should be:
      """
      My\User
      """

    When I run `fp user update {USER_ID} --display_name='My\New\User'`
    Then STDOUT should not be empty

    When I run `fp user get {USER_ID} --field=display_name`
    Then STDOUT should be:
      """
      My\New\User
      """

  Scenario: Don't send user creation emails by default
    Given a FP multisite install

    When I run `fp user create testuser2 testuser2@example.com`
    Then an email should not be sent

    When I run `fp user create testuser3 testuser3@example.com --send-email`
    Then an email should be sent

  Scenario: List URLs of one or more users
    Given a FP install
    And I run `fp user create bob bob@gmail.com --role=contributor`

    When I run `fp user list --include=1,2 --field=url`
    Then STDOUT should be:
      """
      https://example.com/?author=1
      https://example.com/?author=2
      """

  Scenario: Get user with email as login
    Given a FP install
    And I run `fp user create testuser4@example.com testemail4@example.com`

    When I run `fp user get testemail4@example.com --field=user_login`
    Then STDOUT should be:
      """
      testuser4@example.com
      """

    When I run `fp user get testuser4@example.com --field=user_login`
    Then STDOUT should be:
      """
      testuser4@example.com
      """

  Scenario: Mark/remove a user from spam
    Given a FP multisite install
    And I run `fp user create bumblebee bbee@example.com --role=author --porcelain`
    And save STDOUT as {BBEE_ID}
    And I run `fp user create oprime oprime@example.com --role=author --porcelain`
    And save STDOUT as {OP_ID}
    And I run `fp user get bumblebee`
    And STDOUT should not be empty
    And I run `fp user get oprime`
    And STDOUT should not be empty

    When I run `fp site create --slug=foo --porcelain`
    Then save STDOUT as {SPAM_SITE_ID}

    When I run `fp --url=example.com/foo user set-role {BBEE_ID} administrator`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fp user spam {BBEE_ID}`
    Then STDOUT should be:
      """
      User {BBEE_ID} marked as spam.
      Success: Spammed 1 of 1 users.
      """

    When I try the previous command again
    Then STDERR should be:
      """
      Warning: User {BBEE_ID} already marked as spam.
      """
    And STDOUT should be:
      """
      Success: User already spammed.
      """
    And the return code should be 0

    When I run `fp site list --site__in=1 --field=spam`
    Then STDOUT should be:
      """
      0
      """

    When I run `fp site list --site__in={SPAM_SITE_ID} --field=spam`
    Then STDOUT should be:
      """
      1
      """

    When I try `fp user spam {OP_ID} 9999`
    Then STDOUT should be:
      """
      User {OP_ID} marked as spam.
      """
    And STDERR should be:
      """
      Warning: Invalid user ID, email or login: '9999'
      Error: Only spammed 1 of 2 users.
      """
    And the return code should be 1

    When I run `fp user unspam {BBEE_ID}`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fp site list --site__in=1 --field=spam`
    Then STDOUT should be:
      """
      0
      """

    When I run `fp site list --site__in={SPAM_SITE_ID} --field=spam`
    Then STDOUT should be:
      """
      0
      """

  @require-fp-4.3
  Scenario: Sending emails on update
    Given a FP install

    When I run `fp user get 1 --field=user_email`
    Then save STDOUT as {ORIGINAL_EMAIL}

    When I run `fp user update 1 --user_email=different.mail@example.com`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should be sent

    When I run `fp user update 1 --user_email={ORIGINAL_EMAIL} --skip-email`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should not be sent

    When I run `fp user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `fp user update 1 --user_pass=different_password`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should be sent

    When I run `fp user update 1 --user_pass={ORIGINAL_PASSWORD} --skip-email`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should not be sent

  Scenario: Set user url when creating a user
    Given a FP install
    And I run `fp user create testurl sample@email.com --user_url='http://www.testsite.com'`

    When I run `fp user get testurl --fields=user_url`
    Then STDOUT should be a table containing rows:
      | Field        | Value                   |
      | user_url     | http://www.testsite.com |

  Scenario: Support nickname creating and updating user
    Given a FP install

    When I run `fp user create testuser testuser@example.com --nickname=customtestuser --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `fp user meta get {USER_ID} nickname`
    Then STDOUT should be:
      """
      customtestuser
      """

    When I run `fp user update {USER_ID} --nickname=newtestuser`
    And I run `fp user meta get {USER_ID} nickname`
    Then STDOUT should be:
      """
      newtestuser
      """
