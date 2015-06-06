<?php

use WP_CLI\Utils;

include 'checksum.php';

class Security_Command extends WP_CLI_Command {

	/**
	 * Return version number
	 */
	public function version() {
		return '1.0';
	}


	/**
	 * Verify WordPress files against WPCentral.io checksums.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json. Default: table
	 *
	 * @subcommand verify-checksums
	 */
	public function verify_checksums( $args, $assoc_args ) {
		$config_path      = Utils\locate_wp_config();
		$checksum_checker = new Security_Checksum_Check( dirname( $config_path ) );

		$data = $checksum_checker->run();

		if ( isset( $assoc_args['format'] ) && 'json' == $assoc_args['format'] ) {
			\WP_CLI::print_value( json_encode( $data ), array( 'format' => $assoc_args['format'] ) );
		}

		foreach ( $data as $object => $items ) {
			\WP_CLI::line( $object );

			foreach ( $items as $slug => $data ) {
				\WP_CLI::line( '- ' . $slug );

				if ( is_array( $data ) ) {
					foreach ( $data as $line ) {
						\WP_CLI::line( '-- ' . $line );
					}
				}
				else {
					\WP_CLI::line( '-- ' . $data );
				}
			}
		}
	}

}

WP_CLI::add_command( 'security', 'Security_Command' );
