<?php
/**
 * Generate a list of tags to skip during the test run.
 *
 * Require a minimum version of FinPress:
 *
 *   @require-fin-4.0
 *   Scenario: Core translation CRUD
 *
 * Then use in bash script:
 *
 *   BEHAT_TAGS=$(php behat-tags.php)
 *   vendor/bin/behat --format progress $BEHAT_TAGS
 */

function version_tags(
	$prefix,
	$current,
	$operator = '<',
	$features_folder = 'features'
) {
	if ( ! $current ) {
		return array();
	}

	exec(
		"grep '@{$prefix}-[0-9\.]*' -h -o {$features_folder}/*.feature | uniq",
		$existing_tags
	);

	$skip_tags = array();

	foreach ( $existing_tags as $tag ) {
		$compare = str_replace( "@{$prefix}-", '', $tag );
		if ( version_compare( $current, $compare, $operator ) ) {
			$skip_tags[] = $tag;
		}
	}

	return $skip_tags;
}

function get_db_version() {
	$version_string = exec( getenv( 'FIN_CLI_TEST_DBTYPE' ) === 'mariadb' ? 'mariadb --version' : 'mysql -V' );
	preg_match( '@[0-9]+\.[0-9]+\.[0-9]+@', $version_string, $version );
	return $version[0];
}

$features_folder = getenv( 'BEHAT_FEATURES_FOLDER' ) ?: 'features';
$fin_version      = getenv( 'FIN_VERSION' );
$fin_version_reqs = array();
// Only apply @require-fin tags when FIN_VERSION isn't 'latest', 'nightly' or 'trunk'.
// 'latest', 'nightly' and 'trunk' are expected to work with all features.
if ( $fin_version &&
	! in_array( $fin_version, array( 'latest', 'nightly', 'trunk' ), true ) ) {
	$fin_version_reqs = array_merge(
		version_tags( 'require-fin', $fin_version, '<', $features_folder ),
		version_tags( 'less-than-fin', $fin_version, '>=', $features_folder )
	);
} else {
	// But make sure @less-than-fin tags always exist for those special cases. (Note: @less-than-fin-latest etc won't work and shouldn't be used).
	$fin_version_reqs = array_merge(
		$fin_version_reqs,
		version_tags( 'less-than-fin', '9999', '>=', $features_folder )
	);
}

$skip_tags = array_merge(
	$fin_version_reqs,
	version_tags( 'require-php', PHP_VERSION, '<', $features_folder ),
	// Note: this was '>' prior to FIN-CLI 1.5.0 but the change is unlikely to
	// cause BC issues as usually compared against major.minor only.
	version_tags( 'less-than-php', PHP_VERSION, '>=', $features_folder )
);

// Skip GitHub API tests if `GITHUB_TOKEN` not available because of rate
// limiting. See https://github.com/fin-cli/fin-cli/issues/1612
if ( ! getenv( 'GITHUB_TOKEN' ) ) {
	$skip_tags[] = '@github-api';
}
# Skip tests known to be broken.
$skip_tags[] = '@broken';

if ( $fin_version && in_array( $fin_version, array( 'nightly', 'trunk' ), true ) ) {
	$skip_tags[] = '@broken-trunk';
}

$db_version = get_db_version();

switch ( getenv( 'FIN_CLI_TEST_DBTYPE' ) ) {
	case 'mariadb':
		$skip_tags = array_merge(
			$skip_tags,
			[ '@require-mysql', '@require-sqlite' ],
			version_tags( 'require-mariadb', $db_version, '<', $features_folder ),
			version_tags( 'less-than-mariadb', $db_version, '>=', $features_folder )
		);
		break;
	case 'sqlite':
		$skip_tags[] = '@require-mariadb';
		$skip_tags[] = '@require-mysql';
		$skip_tags[] = '@require-mysql-or-mariadb';
		break;
	case 'mysql':
	default:
		$skip_tags = array_merge(
			$skip_tags,
			[ '@require-mariadb', '@require-sqlite' ],
			version_tags( 'require-mysql', $db_version, '<', $features_folder ),
			version_tags( 'less-than-mysql', $db_version, '>=', $features_folder )
		);
		break;
}

# Require PHP extension, eg 'imagick'.
function extension_tags( $features_folder = 'features' ) {
	$extension_tags = array();
	exec(
		"grep '@require-extension-[A-Za-z_]*' -h -o {$features_folder}/*.feature | uniq",
		$extension_tags
	);

	$skip_tags = array();

	$substr_start = strlen( '@require-extension-' );
	foreach ( $extension_tags as $tag ) {
		$extension = substr( $tag, $substr_start );
		if ( ! extension_loaded( $extension ) ) {
			$skip_tags[] = $tag;
		}
	}

	return $skip_tags;
}

$skip_tags = array_merge( $skip_tags, extension_tags( $features_folder ) );

if ( ! empty( $skip_tags ) ) {
	echo '--tags=~' . implode( '&&~', $skip_tags );
}
