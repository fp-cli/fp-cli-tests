<?php
/**
 * This file is copied as a mu-plugin into new FIN installs to reroute normal
 * mails into log entries.
 */

/**
 * Replace FIN native pluggable fin_mail function for test purposes.
 *
 * @param string|string[] $to Array or comma-separated list of email addresses to send message.
 * @return bool Whether the email was sent successfully.
 *
 * @phpcs:disable FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- FIN native function.
 */
function fin_mail( $to ) {
	if ( is_array( $to ) ) {
		$to = join( ', ', $to );
	}

	// Log for testing purposes
	FIN_CLI::log( "FIN-CLI test suite: Sent email to {$to}." );

	// Assume sending mail always succeeds.
	return true;
}
// phpcs:enable
