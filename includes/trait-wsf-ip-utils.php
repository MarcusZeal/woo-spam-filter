<?php
/**
 * IP Utilities Trait for WooCommerce Spam Filter.
 *
 * Shared methods for IP address handling.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trait for IP address utilities.
 */
trait WSF_IP_Utils {

	/**
	 * Get the client IP address.
	 *
	 * Checks various headers in order of reliability to handle proxies,
	 * CDNs (like Cloudflare), and direct connections.
	 *
	 * @return string IP address or 'unknown' if not determinable.
	 */
	protected static function get_client_ip() {
		$ip = '';

		// Check various headers in order of reliability.
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_REAL_IP',        // Nginx proxy.
			'HTTP_X_FORWARDED_FOR',  // General proxy.
			'REMOTE_ADDR',           // Direct connection.
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (take first one - the original client).
				if ( strpos( $value, ',' ) !== false ) {
					$ips = explode( ',', $value );
					$ip  = trim( $ips[0] );
				} else {
					$ip = $value;
				}
				break;
			}
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
	}
}
