Feature: Test that FIN-CLI loads.

  Scenario: FIN-CLI loads for your tests
    Given a FIN install

    When I run `fin eval 'echo "Hello world.";'`
    Then STDOUT should contain:
      """
      Hello world.
      """

  Scenario: FIN Cron is disabled by default
    Given a FIN install
    And a test_cron.php file:
      """
      <?php
      $cron_disabled = defined( "DISABLE_FIN_CRON" ) ? DISABLE_FIN_CRON : false;
      echo 'DISABLE_FIN_CRON is: ' . ( $cron_disabled ? 'true' : 'false' );
      """

    When I run `fin eval-file test_cron.php`
    Then STDOUT should be:
      """
      DISABLE_FIN_CRON is: true
      """

  @require-sqlite
  Scenario: Uses SQLite
    Given a FIN install
    When I run `fin eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """

  @require-mysql
  Scenario: Uses MySQL
    Given a FIN install
    When I run `fin eval 'var_export( defined("DB_ENGINE") );'`
    Then STDOUT should be:
      """
      false
      """

  @require-sqlite
  Scenario: Custom fin-content directory
    Given a FIN install
    And a custom fin-content directory

    When I run `fin eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """

  @require-sqlite
  Scenario: Composer installation
    Given a FIN install with Composer

    When I run `fin eval 'echo DB_ENGINE;'`
    Then STDOUT should contain:
      """
      sqlite
      """
