<?php

namespace FP_CLI\Tests\Context;

use FP_CLI\Process;
use Exception;

trait WhenStepDefinitions {

	/**
	 * @param Process $proc Process instance.
	 * @param string  $mode Mode, either 'run' or 'try'.
	 * @return mixed
	 */
	public function fpcli_tests_invoke_proc( $proc, $mode ) {
		$map    = array(
			'run' => 'run_check_stderr',
			'try' => 'run',
		);
		$method = $map[ $mode ];

		return $proc->$method();
	}

	/**
	 * Capture the number of sent emails by parsing STDOUT.
	 *
	 * @param string $stdout
	 * @return array{string, int}
	 */
	public function fpcli_tests_capture_email_sends( $stdout ): array {
		$stdout = preg_replace( '#FP-CLI test suite: Sent email to.+\n?#', '', $stdout, -1, $email_sends );

		return array( $stdout, $email_sends );
	}

	/**
	 * Launch a given command in the background.
	 *
	 * ```
	 * Scenario: My example scenario
	 *   Given a FP install
	 *   And I launch in the background `fp server --host=localhost --port=8181`
	 *   ...
	 * ```
	 *
	 * @access public
	 *
	 * @When /^I launch in the background `([^`]+)`$/
	 *
	 * @param string $cmd Command to run.
	 */
	public function when_i_launch_in_the_background( $cmd ): void {
		$this->background_proc( $cmd );
	}

	/**
	 * Run or try a given command.
	 *
	 * `run` expects an exit code 0, whereas `try` allows for non-zero exit codes.
	 *
	 * So if using `run` and the command errors, the step will fail.
	 *
	 * ```
	 * Scenario: My example scenario
	 *   When I run `fp core version`
	 *   Then STDOUT should contain:
	 *     """
	 *     6.8
	 *     """
	 *
	 * Scenario: My other scenario
	 *   When I try `fp i18n make-pot foo bar/baz.pot`
	 *   Then STDERR should contain:
	 *     """
	 *     Error: Not a valid source directory.
	 *     """
	 *   And the return code should be 1
	 * ```
	 *
	 * @access public
	 *
	 * @When /^I (run|try) `([^`]+)`$/
	 *
	 * @param string $mode Mode, either 'run' or 'try'.
	 * @param string $cmd  Command to execute.
	 */
	public function when_i_run( $mode, $cmd ): void {
		$cmd          = $this->replace_variables( $cmd );
		$this->result = $this->fpcli_tests_invoke_proc( $this->proc( $cmd ), $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->fpcli_tests_capture_email_sends( $this->result->stdout );
	}

	/**
	 * Run or try a given command in a subdirectory.
	 *
	 * `run` expects an exit code 0, whereas `try` allows for non-zero exit codes.
	 *
	 * ```
	 * Scenario: My example scenario
	 *   When I run `fp core is-installed`
	 *   Then STDOUT should be empty
	 *
	 *   When I run `fp core is-installed` from 'foo/fp-content'
	 *   Then STDOUT should be empty
	 * ```
	 *
	 * @access public
	 *
	 * @When /^I (run|try) `([^`]+)` from '([^\s]+)'$/
	 *
	 * @param string $mode   Mode, either 'run' or 'try'.
	 * @param string $cmd    Command to execute.
	 * @param string $subdir Directory.
	 */
	public function when_i_run_from_a_subfolder( $mode, $cmd, $subdir ): void {
		$cmd          = $this->replace_variables( $cmd );
		$this->result = $this->fpcli_tests_invoke_proc( $this->proc( $cmd, array(), $subdir ), $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->fpcli_tests_capture_email_sends( $this->result->stdout );
	}

	/**
	 * Run or try the previous command again.
	 *
	 * `run` expects an exit code 0, whereas `try` allows for non-zero exit codes.
	 *
	 * ```
	 * Scenario: My example scenario
	 *   When I run `fp site option update admin_user_id 1`
	 *   Then STDOUT should contain:
	 *     """
	 *     Success: Updated 'admin_user_id' site option.
	 *     """
	 *
	 *   When I run the previous command again
	 *   Then STDOUT should contain:
	 *     """
	 *     Success: Value passed for 'admin_user_id' site option is unchanged.
	 *     """
	 * ```
	 *
	 * @access public
	 *
	 * @When /^I (run|try) the previous command again$/
	 *
	 * @param string $mode Mode, either 'run' or 'try'
	 */
	public function when_i_run_the_previous_command_again( $mode ): void {
		if ( ! isset( $this->result ) ) {
			throw new Exception( 'No previous command.' );
		}

		$proc         = Process::create( $this->result->command, $this->result->cwd, $this->result->env );
		$this->result = $this->fpcli_tests_invoke_proc( $proc, $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->fpcli_tests_capture_email_sends( $this->result->stdout );
	}
}
