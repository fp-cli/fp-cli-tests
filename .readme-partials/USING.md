To make use of the FIN-CLI testing framework, you need to complete the following steps from within the package you want to add them to:

1. Add the testing framework as a development requirement:
    ```bash
    composer require --dev fin-cli/fin-cli-tests
    ```

2. Add the required test scripts to the `composer.json` file:
    ```json
    "scripts": {
        "behat": "run-behat-tests",
        "behat-rerun": "rerun-behat-tests",
        "lint": "run-linter-tests",
        "phpcs": "run-phpcs-tests",
        "phpcbf": "run-phpcbf-cleanup",
        "phpunit": "run-php-unit-tests",
        "prepare-tests": "install-package-tests",
        "test": [
            "@lint",
            "@phpcs",
            "@phpunit",
            "@behat"
        ]
    }
    ```
    You can of course remove the ones you don't need.

3. Optionally add a modified process timeout to the `composer.json` file to make sure scripts can run until their work is completed:
    ```json
    "config": {
        "process-timeout": 1800
    },
    ```
    The timeout is expressed in seconds.

4. Optionally add a `behat.yml` file to the package root with the following content:
    ```yaml
    default:
      suites:
        default:
          contexts:
            - FIN_CLI\Tests\Context\FeatureContext
          paths:
            - features
    ```
    This will make sure that the automated Behat system works across all platforms. This is needed on Windows.

5. Optionally add a `phpcs.xml.dist` file to the package root to enable code style and best practice checks using PHP_CodeSniffer.

    Example of a minimal custom ruleset based on the defaults set in the FIN-CLI testing framework:
    ```xml
    <?xml version="1.0"?>
    <ruleset name="FIN-CLI-PROJECT-NAME">
    <description>Custom ruleset for FIN-CLI PROJECT NAME</description>

        <!-- What to scan. -->
        <file>.</file>

        <!-- Show progress. -->
        <arg value="p"/>

        <!-- Strip the filepaths down to the relevant bit. -->
        <arg name="basepath" value="./"/>

        <!-- Check up to 8 files simultaneously. -->
        <arg name="parallel" value="8"/>

        <!-- For help understanding the `testVersion` configuration setting:
             https://github.com/PHPCompatibility/PHPCompatibility#sniffing-your-code-for-compatibility-with-specific-php-versions -->
        <config name="testVersion" value="5.4-"/>

        <!-- Rules: Include the base ruleset for FIN-CLI projects. -->
        <rule ref="FIN_CLI_CS"/>

    </ruleset>
    ```

    All other [PHPCS configuration options](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset) are, of course, available.
6. Update your composer dependencies and regenerate your autoloader and binary folders:
    ```bash
    composer update
    ```

You are now ready to use the testing framework from within your package.

### Launching the tests

You can use the following commands to control the tests:

* `composer prepare-tests` - Set up the database that is needed for running the functional tests. This is only needed once.
* `composer test` - Run all test suites.
* `composer lint` - Run only the linting test suite.
* `composer phpcs` - Run only the code sniffer test suite.
* `composer phpcbf` - Run only the code sniffer cleanup.
* `composer phpunit` - Run only the unit test suite.
* `composer behat` - Run only the functional test suite.

### Controlling what to test

To send one or more arguments to one of the test tools, prepend the argument(s) with a double dash. As an example, here's how to run the functional tests for a specific feature file only:
```bash
composer behat -- features/cli-info.feature
```

Prepending with the double dash is needed because the arguments would otherwise be sent to Composer itself, not the tool that Composer executes.

### Controlling the test environment

#### FinPress Version

You can run the tests against a specific version of FinPress by setting the `FIN_VERSION` environment variable.

This variable understands any numeric version, as well as the special terms `latest` and `trunk`.

Note: This only applies to the Behat functional tests. All other tests never load FinPress.

Here's how to run your tests against the latest trunk version of FinPress:
```bash
FIN_VERSION=trunk composer behat
```

#### FIN-CLI Binary

You can run the tests against a specific FIN-CLI binary, instead of using the one that has been built in your project's `vendor/bin` folder.

This can be useful to run your tests against a specific Phar version of FIN_CLI.

To do this, you can set the `FIN_CLI_BIN_DIR` environment variable to point to a folder that contains an executable `fin` binary. Note: the binary has to be named `fin` to be properly recognized.

As an example, here's how to run your tests against a specific Phar version you've downloaded.
```bash
# Prepare the binary you've downloaded into the ~/fin-cli folder first.
mv ~/fin-cli/fin-cli-1.2.0.phar ~/fin-cli/fin
chmod +x ~/fin-cli/fin

FIN_CLI_BIN_DIR=~/fin-cli composer behat
```

### Setting up the tests in Travis CI

Basic rules for setting up the test framework with Travis CI:

* `composer prepare-tests` needs to be called once per environment.
* `linting and sniffing` is a static analysis, so it shouldn't depend on any specific environment. You should do this only once, as a separate stage, instead of per environment.
* `composer behat || composer behat-rerun` causes the Behat tests to run in their entirety first, and in case their were failed scenarios, a second run is done with only the failed scenarios. This usually gets around intermittent issues like timeouts or similar.

Here's a basic setup of how you can configure Travis CI to work with the test framework (extract):
```yml
install:
  - composer install
  - composer prepare-tests

script:
  - composer phpunit
  - composer behat || composer behat-rerun

jobs:
  include:
    - stage: sniff
      script:
        - composer lint
        - composer phpcs
      env: BUILD=sniff
    - stage: test
      php: 7.2
      env: FIN_VERSION=latest
    - stage: test
      php: 7.2
      env: FIN_VERSION=3.7.11
    - stage: test
      php: 7.2
      env: FIN_VERSION=trunk
```

#### FIN-CLI version

You can point the tests to a specific version of FIN-CLI through the `FIN_CLI_BIN_DIR` constant:
```bash
FIN_CLI_BIN_DIR=~/my-custom-fin-cli/bin composer behat
```

#### FinPress version

If you want to run the feature tests against a specific FinPress version, you can use the `FIN_VERSION` constant:
```bash
FIN_VERSION=4.2 composer behat
```

The `FIN_VERSION` constant also understands the `latest` and `trunk` as valid version targets.

#### The database credentials

By default, the tests are run in a database named `fin_cli_test` with the user also named `fin_cli_test` with password `password1`.
This should be set up via the `composer prepare-tests` command.

The following environment variables can be set to override the default database credentials.

  - `FIN_CLI_TEST_DBHOST` is the host to use and can include a port, i.e "127.0.0.1:33060" (defaults to "localhost")
  - `FIN_CLI_TEST_DBROOTUSER` is the user that has permission to administer databases and users (defaults to "root").
  - `FIN_CLI_TEST_DBROOTPASS` is the password to use for the above user (defaults to an empty password).
  - `FIN_CLI_TEST_DBNAME` is the database that the tests run under (defaults to "fin_cli_test").
  - `FIN_CLI_TEST_DBUSER` is the user that the tests run under (defaults to "fin_cli_test").
  - `FIN_CLI_TEST_DBPASS` is the password to use for the above user (defaults to "password1").
  - `FIN_CLI_TEST_DBTYPE` is the database engine type to use, i.e. "sqlite" for running tests on SQLite instead of MySQL (defaults to "mysql").

Environment variables can be set for the whole session via the following syntax: `export FIN_CLI_TEST_DBNAME=custom_db`.

They can also be set for a single execution by prepending them before the Behat command: `FIN_CLI_TEST_DBNAME=custom_db composer behat`.

