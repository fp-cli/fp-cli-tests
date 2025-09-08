Feature: Test that FP-CLI loads.

  Scenario: FP-CLI loads for your tests
    Given a FP install

    When I run `fp eval 'echo "Hello world.";'`
    Then STDOUT should contain:
      """
      Hello world.
      """

  Scenario: FP Cron is disabled by default
    Given a FP install
    And a test_cron.php file:
      """
      <?php
      $cron_disabled = defined( "DISABLE_FP_CRON" ) ? DISABLE_FP_CRON : false;
      echo 'DISABLE_FP_CRON is: ' . ( $cron_disabled ? 'true' : 'false' );
      """

    When I run `fp eval-file test_cron.php`
    Then STDOUT should be:
      """
      DISABLE_FP_CRON is: true
      """

  @require-sqlite
  Scenario: Uses SQLite
    Given a FP install
    When I run `fp eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """

  @require-mysql
  Scenario: Uses MySQL
    Given a FP install
    When I run `fp eval 'var_export( defined("DB_ENGINE") );'`
    Then STDOUT should be:
      """
      false
      """

  @require-sqlite
  Scenario: Custom fp-content directory
    Given a FP install
    And a custom fp-content directory

    When I run `fp eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """

  @require-sqlite
  Scenario: Composer installation
    Given a FP install with Composer

    When I run `fp eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """
