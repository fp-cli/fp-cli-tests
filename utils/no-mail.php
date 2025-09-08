<?php
/**
 * This file is copied as a mu-plugin into new FP installs to reroute normal
 * mails into log entries.
 */

/**
 * Replace FP native pluggable fp_mail function for test purposes.
 *
 * @param string|string[] $to Array or comma-separated list of email addresses to send message.
 * @return bool Whether the email was sent successfully.
 *
 * @phpcs:disable FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- FP native function.
 */
function fp_mail( $to ) {
	if ( is_array( $to ) ) {
		$to = join( ', ', $to );
	}

	// Log for testing purposes
	FP_CLI::log( "FP-CLI test suite: Sent email to {$to}." );

	// Assume sending mail always succeeds.
	return true;
}
// phpcs:enable
