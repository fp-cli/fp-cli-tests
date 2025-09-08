<?php
define( 'FP_CLI', true );
define( 'FP_CLI_TESTS_ROOT', dirname( __DIR__ ) );

// These constants are actively used by dependent projects, renaming them would be a BC-break.
// phpcs:disable FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define(
	'VENDOR_DIR',
	file_exists( FP_CLI_TESTS_ROOT . '/vendor/autoload.php' )
		? FP_CLI_TESTS_ROOT . '/vendor'
		: FP_CLI_TESTS_ROOT . '/../..'
);
define( 'PACKAGE_ROOT', VENDOR_DIR . '/..' );
// phpcs:enable

define(
	'FP_CLI_ROOT',
	is_readable( PACKAGE_ROOT . '/VERSION' )
		? PACKAGE_ROOT
		: VENDOR_DIR . '/fp-cli/fp-cli'
);

define(
	'FP_CLI_VERSION',
	is_readable( FP_CLI_ROOT . '/VERSION' )
		? trim( file_get_contents( FP_CLI_ROOT . '/VERSION' ) )
		: '2.x.x'
);

require_once VENDOR_DIR . '/autoload.php';
require_once FP_CLI_ROOT . '/php/utils.php';
require_once __DIR__ . '/includes/TestCase.php';

/**
 * @param string[] $config_filenames List of config file names to look for.
 * @return void
 */
function fpcli_tests_include_config( array $config_filenames = [] ): void {
	$config_filename = false;
	foreach ( $config_filenames as $filename ) {
		if ( file_exists( PACKAGE_ROOT . '/' . $filename ) ) {
			$config_filename = PACKAGE_ROOT . '/' . $filename;
			break;
		}
	}

	if ( $config_filename ) {
		$config  = file_get_contents( $config_filename );
		$matches = null;
		$pattern = '/bootstrap="(?P<bootstrap>[^"]*)"/';
		$result  = preg_match( $pattern, $config, $matches );
		if ( isset( $matches['bootstrap'] ) && file_exists( $matches['bootstrap'] ) ) {
			include_once PACKAGE_ROOT . '/' . $matches['bootstrap'];
		}
	}
}

fpcli_tests_include_config(
	[
		'phpunit.xml',
		'.phpunit.xml',
		'phpunit.xml.dist',
		'.phpunit.xml.dist',
	]
);
