<?php
/**
 * Logger class for WooCommerce Spam Filter.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles database logging of blocked/flagged requests.
 */
class WSF_Logger {

	use WSF_IP_Utils;

    /**
     * Stats cache key.
     */
    const STATS_CACHE_KEY = 'wsf_stats_cache';

    /**
     * Stats cache duration in seconds.
     */
    const STATS_CACHE_DURATION = 300; // 5 minutes.

    /**
     * Get the table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wsf_blocked_requests';
    }

    /**
     * Create the database table.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            endpoint varchar(255) NOT NULL,
            user_agent text,
            request_data longtext,
            blocked_reason varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'logged',
            country_code varchar(2) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY status (status),
            KEY created_at (created_at),
            KEY country_code (country_code)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log a blocked/flagged request.
     *
     * @param string $endpoint       The endpoint that was accessed.
     * @param string $blocked_reason The reason for blocking/flagging.
     * @param array  $request_data   Additional request data.
     * @param string $status         Status: 'blocked' or 'logged'.
     */
    public static function log_blocked_request( $endpoint, $blocked_reason, $request_data = array(), $status = 'logged' ) {
        global $wpdb;

        $ip_address = self::get_client_ip();
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        // Get country code.
        $country_code = self::get_country_code( $ip_address );

        // Sanitize request data.
        $sanitized_data = array();
        if ( ! empty( $request_data ) ) {
            foreach ( $request_data as $key => $value ) {
                $sanitized_key = sanitize_key( $key );
                if ( is_array( $value ) ) {
                    $sanitized_data[ $sanitized_key ] = array_map( 'sanitize_text_field', $value );
                } elseif ( is_bool( $value ) ) {
                    $sanitized_data[ $sanitized_key ] = $value;
                } else {
                    $sanitized_data[ $sanitized_key ] = sanitize_text_field( $value );
                }
            }
        }

        $wpdb->insert(
            self::get_table_name(),
            array(
                'ip_address'     => sanitize_text_field( $ip_address ),
                'endpoint'       => sanitize_text_field( $endpoint ),
                'user_agent'     => $user_agent,
                'request_data'   => wp_json_encode( $sanitized_data ),
                'blocked_reason' => sanitize_text_field( $blocked_reason ),
                'status'         => in_array( $status, array( 'blocked', 'logged' ), true ) ? $status : 'logged',
                'country_code'   => $country_code,
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        // Clear stats cache.
        delete_transient( self::STATS_CACHE_KEY );
    }

    /**
     * Get country code from IP address using free API.
     *
     * @param string $ip IP address.
     * @return string|null Two-letter country code or null.
     */
    public static function get_country_code( $ip ) {
        // Skip for local/private IPs.
        if ( in_array( $ip, array( '127.0.0.1', '::1', 'unknown' ), true ) ||
             filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
            return null;
        }

        // Check cache first.
        $cache_key = 'wsf_geo_' . md5( $ip );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached ?: null;
        }

        // Use ip-api.com (free, no API key required, 45 requests/minute limit).
        $response = wp_remote_get(
            'http://ip-api.com/json/' . $ip . '?fields=countryCode',
            array( 'timeout' => 2 )
        );

        $country_code = null;
        if ( ! is_wp_error( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['countryCode'] ) ) {
                $country_code = sanitize_text_field( $body['countryCode'] );
            }
        }

        // Cache for 24 hours (even empty results to prevent repeated lookups).
        set_transient( $cache_key, $country_code ?: '', DAY_IN_SECONDS );

        return $country_code;
    }

    /**
     * Get country name from code.
     *
     * @param string $code Two-letter country code.
     * @return string Country name.
     */
    public static function get_country_name( $code ) {
        $countries = array(
            'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia',
            'DE' => 'Germany', 'FR' => 'France', 'IT' => 'Italy', 'ES' => 'Spain', 'NL' => 'Netherlands',
            'BR' => 'Brazil', 'IN' => 'India', 'CN' => 'China', 'JP' => 'Japan', 'KR' => 'South Korea',
            'RU' => 'Russia', 'UA' => 'Ukraine', 'PL' => 'Poland', 'RO' => 'Romania', 'CZ' => 'Czech Republic',
            'VN' => 'Vietnam', 'ID' => 'Indonesia', 'TH' => 'Thailand', 'PH' => 'Philippines', 'MY' => 'Malaysia',
            'SG' => 'Singapore', 'HK' => 'Hong Kong', 'TW' => 'Taiwan', 'MX' => 'Mexico', 'AR' => 'Argentina',
            'CL' => 'Chile', 'CO' => 'Colombia', 'ZA' => 'South Africa', 'NG' => 'Nigeria', 'EG' => 'Egypt',
            'TR' => 'Turkey', 'SA' => 'Saudi Arabia', 'AE' => 'UAE', 'IL' => 'Israel', 'SE' => 'Sweden',
            'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'BE' => 'Belgium', 'AT' => 'Austria',
            'CH' => 'Switzerland', 'IE' => 'Ireland', 'PT' => 'Portugal', 'GR' => 'Greece', 'HU' => 'Hungary',
            'NZ' => 'New Zealand', 'BD' => 'Bangladesh', 'PK' => 'Pakistan',
        );

        return isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
    }

    /**
     * Build WHERE clauses for log queries.
     *
     * @param string $status     Filter by status ('all', 'blocked', 'logged').
     * @param string $ip_search  Filter by IP address.
     * @param string $date_from  Filter from date (Y-m-d).
     * @param string $date_to    Filter to date (Y-m-d).
     * @return array Array with 'sql' string and 'values' array.
     */
    private static function build_where_clauses( $status = 'all', $ip_search = '', $date_from = '', $date_to = '' ) {
        global $wpdb;

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( 'all' !== $status ) {
            $where_clauses[] = 'status = %s';
            $where_values[]  = $status;
        }

        if ( ! empty( $ip_search ) ) {
            $where_clauses[] = 'ip_address LIKE %s';
            $where_values[]  = '%' . $wpdb->esc_like( $ip_search ) . '%';
        }

        if ( ! empty( $date_from ) ) {
            $where_clauses[] = 'DATE(created_at) >= %s';
            $where_values[]  = $date_from;
        }

        if ( ! empty( $date_to ) ) {
            $where_clauses[] = 'DATE(created_at) <= %s';
            $where_values[]  = $date_to;
        }

        return array(
            'sql'    => implode( ' AND ', $where_clauses ),
            'values' => $where_values,
        );
    }

    /**
     * Get logs with pagination and filters.
     *
     * @param int    $page       Page number.
     * @param int    $per_page   Items per page.
     * @param string $status     Filter by status ('all', 'blocked', 'logged').
     * @param string $orderby    Column to order by.
     * @param string $order      Order direction (ASC or DESC).
     * @param string $ip_search  Filter by IP address.
     * @param string $date_from  Filter from date (Y-m-d).
     * @param string $date_to    Filter to date (Y-m-d).
     * @return array
     */
    public static function get_logs( $page = 1, $per_page = 20, $status = 'all', $orderby = 'created_at', $order = 'DESC', $ip_search = '', $date_from = '', $date_to = '' ) {
        global $wpdb;

        $table_name = self::get_table_name();
        $offset     = ( $page - 1 ) * $per_page;

        // Whitelist allowed columns.
        $allowed_orderby = array( 'id', 'ip_address', 'endpoint', 'blocked_reason', 'status', 'created_at', 'country_code' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        $order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

        // Build WHERE clauses.
        $where = self::build_where_clauses( $status, $ip_search, $date_from, $date_to );

        // Build query.
        $query          = "SELECT * FROM {$table_name} WHERE {$where['sql']} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $query_values   = $where['values'];
        $query_values[] = $per_page;
        $query_values[] = $offset;

        $results = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore

        return $results ? $results : array();
    }

    /**
     * Get total log count with filters.
     *
     * @param string $status     Filter by status ('all', 'blocked', 'logged').
     * @param string $ip_search  Filter by IP address.
     * @param string $date_from  Filter from date (Y-m-d).
     * @param string $date_to    Filter to date (Y-m-d).
     * @return int
     */
    public static function get_total_count( $status = 'all', $ip_search = '', $date_from = '', $date_to = '' ) {
        global $wpdb;

        $table_name = self::get_table_name();
        $where      = self::build_where_clauses( $status, $ip_search, $date_from, $date_to );

        if ( empty( $where['values'] ) ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore
        }

        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE {$where['sql']}", $where['values'] ) ); // phpcs:ignore
    }

    /**
     * Get statistics (uncached).
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        $table_name = self::get_table_name();

        // Total logged.
        $total_logged = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", // phpcs:ignore
                'logged'
            )
        );

        // Total blocked.
        $total_blocked = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", // phpcs:ignore
                'blocked'
            )
        );

        // Logged today.
        $logged_today = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND DATE(created_at) = %s", // phpcs:ignore
                'logged',
                current_time( 'Y-m-d' )
            )
        );

        // Blocked today.
        $blocked_today = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND DATE(created_at) = %s", // phpcs:ignore
                'blocked',
                current_time( 'Y-m-d' )
            )
        );

        // This week.
        $week_start = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

        $logged_week = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND created_at >= %s", // phpcs:ignore
                'logged',
                $week_start
            )
        );

        $blocked_week = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND created_at >= %s", // phpcs:ignore
                'blocked',
                $week_start
            )
        );

        // Unique IPs.
        $unique_ips = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT ip_address) FROM {$table_name}" ); // phpcs:ignore

        return array(
            'total_logged'  => $total_logged,
            'total_blocked' => $total_blocked,
            'logged_today'  => $logged_today,
            'blocked_today' => $blocked_today,
            'logged_week'   => $logged_week,
            'blocked_week'  => $blocked_week,
            'unique_ips'    => $unique_ips,
        );
    }

    /**
     * Get statistics with caching.
     *
     * @return array
     */
    public static function get_stats_cached() {
        $cached = get_transient( self::STATS_CACHE_KEY );

        if ( false !== $cached ) {
            return $cached;
        }

        $stats = self::get_stats();
        set_transient( self::STATS_CACHE_KEY, $stats, self::STATS_CACHE_DURATION );

        return $stats;
    }

    /**
     * Get chart data for the last N days.
     *
     * @param int $days Number of days.
     * @return array
     */
    public static function get_chart_data( $days = 14 ) {
        global $wpdb;

        $table_name = self::get_table_name();
        $start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as date, status, COUNT(*) as count
                 FROM {$table_name}
                 WHERE DATE(created_at) >= %s
                 GROUP BY DATE(created_at), status
                 ORDER BY date ASC", // phpcs:ignore
                $start_date
            ),
            ARRAY_A
        );

        // Build data structure.
        $data = array(
            'labels'  => array(),
            'logged'  => array(),
            'blocked' => array(),
        );

        // Initialize all days.
        for ( $i = $days; $i >= 0; $i-- ) {
            $date                  = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $data['labels'][]      = gmdate( 'M j', strtotime( $date ) );
            $data['logged'][ $date ]  = 0;
            $data['blocked'][ $date ] = 0;
        }

        // Fill in actual data.
        foreach ( $results as $row ) {
            if ( isset( $data['logged'][ $row['date'] ] ) ) {
                if ( 'logged' === $row['status'] ) {
                    $data['logged'][ $row['date'] ] = (int) $row['count'];
                } else {
                    $data['blocked'][ $row['date'] ] = (int) $row['count'];
                }
            }
        }

        // Convert to indexed arrays.
        $data['logged']  = array_values( $data['logged'] );
        $data['blocked'] = array_values( $data['blocked'] );

        return $data;
    }

    /**
     * Clear all logs.
     *
     * @return bool
     */
    public static function clear_logs() {
        global $wpdb;

        $table_name = self::get_table_name();
        $result     = $wpdb->query( "TRUNCATE TABLE {$table_name}" ) !== false; // phpcs:ignore

        // Clear stats cache.
        delete_transient( self::STATS_CACHE_KEY );

        return $result;
    }

    /**
     * Auto-cleanup old logs.
     */
    public static function auto_cleanup() {
        if ( '1' !== get_option( 'wsf_auto_cleanup', '1' ) ) {
            return;
        }

        $days = (int) get_option( 'wsf_cleanup_days', 30 );
        if ( $days < 1 ) {
            return;
        }

        global $wpdb;

        $table_name = self::get_table_name();
        $cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s", // phpcs:ignore
                $cutoff
            )
        );

        // Clear stats cache.
        delete_transient( self::STATS_CACHE_KEY );
    }

    /**
     * Get all logs for export with filters.
     *
     * @param string $status    Filter by status.
     * @param string $date_from Filter from date.
     * @param string $date_to   Filter to date.
     * @return array
     */
    public static function get_all_logs( $status = 'all', $date_from = '', $date_to = '' ) {
        global $wpdb;

        $table_name = self::get_table_name();
        $where      = self::build_where_clauses( $status, '', $date_from, $date_to );

        if ( empty( $where['values'] ) ) {
            return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        }

        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE {$where['sql']} ORDER BY created_at DESC", $where['values'] ), ARRAY_A ); // phpcs:ignore
    }

    /**
     * Add IP to whitelist from logs.
     *
     * @param string $ip IP address to whitelist.
     * @return bool
     */
    public static function whitelist_ip( $ip ) {
        $ip        = sanitize_text_field( $ip );
        $whitelist = get_option( 'wsf_whitelist_ips', '' );

        if ( empty( $whitelist ) ) {
            return update_option( 'wsf_whitelist_ips', $ip );
        }

        $ips = array_map( 'trim', explode( ',', $whitelist ) );

        if ( in_array( $ip, $ips, true ) ) {
            return true; // Already whitelisted.
        }

        $ips[] = $ip;
        return update_option( 'wsf_whitelist_ips', implode( ', ', $ips ) );
    }

    /**
     * Bulk whitelist multiple IPs.
     *
     * @param array $ips Array of IP addresses.
     * @return int Number of IPs added.
     */
    public static function bulk_whitelist_ips( $ips ) {
        $whitelist = get_option( 'wsf_whitelist_ips', '' );
        $existing  = empty( $whitelist ) ? array() : array_map( 'trim', explode( ',', $whitelist ) );

        $added = 0;
        foreach ( $ips as $ip ) {
            $ip = sanitize_text_field( $ip );
            if ( ! empty( $ip ) && ! in_array( $ip, $existing, true ) ) {
                $existing[] = $ip;
                $added++;
            }
        }

        if ( $added > 0 ) {
            update_option( 'wsf_whitelist_ips', implode( ', ', $existing ) );
        }

        return $added;
    }
}
