Feature: Manage user custom fields

  # SQLite requires FinPress 6.0+.
  @less-than-php-8.0 @require-mysql
  Scenario: User application passwords are disabled for FinPress lower than 5.6
    Given a FP install
    And I try `fp theme install twentytwenty --activate`
    And I run `fp core download --version=5.5 --force`

    When I try `fp user application-password create 1 myapp`
    Then STDERR should contain:
      """
      Error: Requires FinPress 5.6 or greater.
      """

    When I try `fp user application-password list 1`
    Then STDERR should contain:
      """
      Error: Requires FinPress 5.6 or greater.
      """

    When I try `fp user application-password get 1 123`
    Then STDERR should contain:
      """
      Error: Requires FinPress 5.6 or greater.
      """

    When I try `fp user application-password delete 1 123`
    Then STDERR should contain:
      """
      Error: Requires FinPress 5.6 or greater.
      """

  @require-fp-5.6
  Scenario: User application password CRUD
    Given a FP install

    When I run `fp user application-password create 1 myapp`
    Then STDOUT should not be empty

    When I run `fp user application-password list 1`
    Then STDOUT should contain:
      """
      myapp
      """

    When I run `fp user application-password list 1 --name=myapp --field=uuid`
    And save STDOUT as {UUID}
    And I run `fp user application-password get 1 {UUID}`
    Then STDOUT should contain:
      """
      myapp
      """

    When I try `fp user application-password get 2 {UUID}`
    Then STDERR should be:
      """
      Error: Invalid user ID, email or login: '2'
      """
    And the return code should be 1

    When I try `fp user application-password get 1 123`
    Then STDERR should be:
      """
      Error: No application password found for this user ID and UUID.
      """
    And the return code should be 1

    When I run `fp user application-password update 1 {UUID} --name=anotherapp`
    Then STDOUT should not be empty

    When I run `fp user application-password get 1 {UUID}`
    Then STDOUT should contain:
      """
      anotherapp
      """
    And STDOUT should not contain:
      """
      myapp
      """

    When I run `fp user application-password delete 1 {UUID}`
    Then STDOUT should contain:
      """
      Success: Deleted 1 of 1 application password.
      """

    When I try `fp user application-password get 1 {UUID}`
    Then the return code should be 1

    When I run `fp user application-password create 1 myapp1`
    And I run `fp user application-password create 1 myapp2`
    And I run `fp user application-password create 1 myapp3`
    And I run `fp user application-password create 1 myapp4`
    And I run `fp user application-password create 1 myapp5`
    Then STDOUT should not be empty

    When I run `fp user application-password list 1 --format=count`
    Then STDOUT should be:
      """
      5
      """

    When I run `fp user application-password list 1 --name=myapp1 --field=uuid`
    Then save STDOUT as {UUID1}

    When I run `fp user application-password list 1 --name=myapp2 --field=uuid`
    Then save STDOUT as {UUID2}

    When I try `fp user application-password delete 1 {UUID1} {UUID2} nonsense`
    Then STDERR should contain:
      """
      Warning: Failed to delete UUID nonsense
      """
    And STDOUT should contain:
      """
      Success: Deleted 2 of 3 application passwords.
      """

    When I run `fp user application-password list 1 --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `fp user application-password delete 1 --all`
    And I run `fp user application-password list 1 --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-fp-5.6
  Scenario: List user application passwords
    Given a FP install

    When I run `fp user application-password create 1 myapp1`
    Then STDOUT should not be empty

    When I run `fp user application-password create 1 myapp2 --app-id=42`
    Then STDOUT should not be empty

    When I run `fp user application-password list 1 --name=myapp1 --field=uuid`
    Then save STDOUT as {UUID1}

    When I run `fp user application-password list 1 --name=myapp2 --field=uuid`
    Then save STDOUT as {UUID2}

    When I run `fp user application-password list 1 --name=myapp1 --field=password`
    Then save STDOUT as {HASH1}

    When I run `fp user application-password list 1 --name=myapp1 --field=password | sed 's#/#\\\/#g'`
    Then save STDOUT as {JSONHASH1}

    When I run `fp user application-password list 1 --name=myapp2 --field=password`
    Then save STDOUT as {HASH2}

    When I run `fp user application-password list 1 --name=myapp2 --field=password | sed 's#/#\\\/#g'`
    Then save STDOUT as {JSONHASH2}

    When I run `fp user application-password list 1 --name=myapp1 --field=created`
    Then save STDOUT as {CREATED1}

    When I run `fp user application-password list 1 --name=myapp2 --field=created`
    Then save STDOUT as {CREATED2}

    When I run `fp user application-password list 1 --format=json`
    Then STDOUT should contain:
      """
      {"uuid":"{UUID1}","app_id":"","name":"myapp1","password":"{JSONHASH1}","created":{CREATED1},"last_used":null,"last_ip":null}
      """
    And STDOUT should contain:
      """
      {"uuid":"{UUID2}","app_id":"42","name":"myapp2","password":"{JSONHASH2}","created":{CREATED2},"last_used":null,"last_ip":null}
      """

    When I run `fp user application-password list 1 --format=json --fields=uuid,name`
    Then STDOUT should contain:
      """
      {"uuid":"{UUID1}","name":"myapp1"}
      """
    And STDOUT should contain:
      """
      {"uuid":"{UUID2}","name":"myapp2"}
      """

    When I run `fp user application-password list 1`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   | password | created    | last_used | last_ip |
      | {UUID2} | 42     | myapp2 | {HASH2}  | {CREATED2} |           |         |
      | {UUID1} |        | myapp1 | {HASH1}  | {CREATED1} |           |         |

    When I run `fp user application-password list 1 --fields=uuid,app_id,name`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   |
      | {UUID2} | 42     | myapp2 |
      | {UUID1} |        | myapp1 |

    When I run `fp user application-password list admin`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   | password | created    | last_used | last_ip |
      | {UUID2} | 42     | myapp2 | {HASH2}  | {CREATED2} |           |         |
      | {UUID1} |        | myapp1 | {HASH1}  | {CREATED1} |           |         |

    When I run `fp user application-password list admin --orderby=created --order=asc`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   | password | created    | last_used | last_ip |
      | {UUID1} |        | myapp1 | {HASH1}  | {CREATED1} |           |         |
      | {UUID2} | 42     | myapp2 | {HASH2}  | {CREATED2} |           |         |

    When I run `fp user application-password list admin --orderby=name --order=asc`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   | password | created    | last_used | last_ip |
      | {UUID1} |        | myapp1 | {HASH1}  | {CREATED1} |           |         |
      | {UUID2} | 42     | myapp2 | {HASH2}  | {CREATED2} |           |         |

    When I run `fp user application-password list admin --orderby=name --order=desc`
    Then STDOUT should be a table containing rows:
      | uuid    | app_id | name   | password | created    | last_used | last_ip |
      | {UUID2} | 42     | myapp2 | {HASH2}  | {CREATED2} |           |         |
      | {UUID1} |        | myapp1 | {HASH1}  | {CREATED1} |           |         |

    When I run `fp user application-password list admin --name=myapp2 --format=json`
    Then STDOUT should contain:
      """
      myapp2
      """
    And STDOUT should not contain:
      """
      myapp1
      """

    When I run `fp user application-password list admin --field=name`
    Then STDOUT should contain:
      """
      myapp1
      """
    And STDOUT should contain:
      """
      myapp2
      """

    When I run `fp user application-password list 1 --field=name --app-id=42`
    Then STDOUT should be:
      """
      myapp2
      """

    When I run `fp user application-password list 1 --format=ids --orderby=name --order=asc`
    Then STDOUT should be:
      """
      {UUID1} {UUID2}
      """

  # FinPress 6.8 uses BLAKE2b with fp_fast_hash() / fp_verify_fast_hash() for hashing application passwords.
  # See https://make.finpress.org/core/2025/02/17/finpress-6-8-will-use-bcrypt-for-password-hashing/
  @require-fp-5.6 @less-than-fp-6.8
  Scenario: Get particular user application password hash
    Given a FP install

    When I run `fp user create testuser testuser@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I try the previous command again
    Then the return code should be 1

    When I run `fp user application-password create {USER_ID} someapp --porcelain`
    Then save STDOUT as {PASSWORD}

    When I run `fp user application-password list {USER_ID} --name=someapp --field=uuid`
    Then save STDOUT as {UUID}

    When I run `fp user application-password get {USER_ID} {UUID} --field=password | sed 's/\$/\\\$/g'`
    Then save STDOUT as {HASH}

    When I run `fp eval "var_export( fp_check_password( '{PASSWORD}', '{HASH}', {USER_ID} ) );"`
    Then STDOUT should contain:
      """
      true
      """

  @require-fp-6.8
  Scenario: Get particular user application password hash
    Given a FP install

    When I run `fp user create testuser testuser@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I try the previous command again
    Then the return code should be 1

    When I run `fp user application-password create {USER_ID} someapp --porcelain`
    Then save STDOUT as {PASSWORD}

    When I run `fp user application-password list {USER_ID} --name=someapp --field=uuid`
    Then save STDOUT as {UUID}

    When I run `fp user application-password get {USER_ID} {UUID} --field=password | sed 's/\$/\\\$/g'`
    Then save STDOUT as {HASH}

    When I run `fp eval "var_export( fp_verify_fast_hash( '{PASSWORD}', '{HASH}', {USER_ID} ) );"`
    Then STDOUT should contain:
      """
      true
      """
