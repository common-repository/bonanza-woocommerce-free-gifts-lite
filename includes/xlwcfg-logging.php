<?php
defined( 'ABSPATH' ) || exit;
/**
 * this add logs in txt file inside WP uploads folder
 *
 * @param string $string
 * @param type $filename
 *
 * @return boolean
 */
if ( ! function_exists( 'xlwcfg_force_log' ) ) {
	function xlwcfg_force_log( $string, $filename = 'force.txt', $mode = 'a' ) {

		if ( empty( $string ) ) {
			return false;
		}

		if ( ( XLWCFG_Common::$is_force_debug === true ) || ( WP_DEBUG === true && ! is_admin() ) ) {

			$upload_dir = wp_upload_dir();
			$base_path  = $upload_dir['basedir'] . '/xlplugins/free-gifts';

			if ( ! file_exists( $base_path ) ) {
				mkdir( $base_path, 0777, true );
			}

			$file_path = $base_path . '/' . $filename;
			$file      = fopen( $file_path, $mode );
			$curTime   = current_time( "M d, Y H.i.s" ) . ': ';
			$string    = "\r\n" . $curTime . $string;
			fwrite( $file, $string );
			fclose( $file );

			return true;
		}

	}
}

if ( ! function_exists( 'xlplugins_force_log' ) ) {
	function xlplugins_force_log( $string, $filename = "force.txt", $mode = 'a' ) {

		if ( empty( $string ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$base_path  = $upload_dir['basedir'] . '/xlplugins';

		if ( ! file_exists( $base_path ) ) {
			mkdir( $base_path, 0777, true );
		}
		$filename  = str_replace( '.txt', '-' . date( "Y-m" ) . '.txt', $filename );
		$file_path = $base_path . '/' . $filename;
		$file      = fopen( $file_path, $mode );
		$curTime   = current_time( "M d, Y H.i.s" ) . ': ';
		$string    = "\r\n" . $curTime . $string;
		fwrite( $file, $string );
		fclose( $file );

		return true;
	}
}