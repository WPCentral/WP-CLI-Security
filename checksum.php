<?php

class Security_Checksum_Check {
	const url = 'http://wpcentral.io/api/checksums/';

	private $root_path;
	private $object_results = array(
		'plugin' => array(),
		'theme'  => array(),
	);

	public function __construct( $path = '' ) {
		$this->root_path = $path;
	}

	public function run() {
		$vcs_dirs = array( '.svn', '.git', '.hg', '.bzr' );
		$plugins  = get_plugins();

		$object = 'plugin';
		foreach ( $plugins as $file => $plugin ) {
			$slug = dirname( $file );
			$path = rtrim( dirname( wp_normalize_path( WP_PLUGIN_DIR ) . '/' . $file ), '\\/' ) . '/';

			foreach ( $vcs_dirs as $vcs_dir ) {
				if ( $checkout = file_exists( $path . $vcs_dir ) ) {
					$this->object_results[ $object ][ $slug ] = "Version controlled";
					continue 2;
				}
			}

			$checksums = $this->get_checksums( $object, $slug, $plugin['Version'] );

			if ( $checksums ) {
				$this->object_results[ $object ][ $slug ] = $this->compare( $path, $checksums );
			}
		}

		return $this->object_results;
	}

	private function get_checksums( $object, $slug, $version ) {
		$response     = wp_remote_get( self::url . $object . '/' . $slug . '/' . $version );
		$api_response = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $api_response ) {
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				return $api_response;
			}
			else {
				$this->object_results[ $object ][ $slug ] = $api_response[0]->message;
			}
		}
		else {
			$this->object_results[ $object ][ $slug ] = "Couldn't connect to the server";
		}

		return false;
	}


	private function compare( $path, $checksums ) {
		$errors = array();

		foreach ( $checksums as $item ) {
			if ( ! file_exists( $path . $item->file ) ) {
				$errors[] = "File doesn't exist: {$item->file}";
				continue;
			}

			$md5_file = md5_file( $path . $item->file );

			if ( $md5_file !== $item->checksum ) {
				$errors[] = "File doesn't verify against checksum: {$item->file}";
			}
		}

		return $errors;
	}

}