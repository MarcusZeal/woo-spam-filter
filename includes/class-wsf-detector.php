<?php
/**
 * Detector class for WooCommerce Spam Filter.
 *
 * Zero false positive approach using JavaScript token verification.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects and blocks suspicious checkout requests.
 *
 * Detection Strategy:
 * 1. JavaScript Token - Set when checkout page loads, required for API requests
 * 2. WooCommerce Session - Verify user has browsed the store
 * 3. Rate Limiting - Block IPs with excessive failed attempts
 *
 * Key Principle: NEVER block a request that could be legitimate.
 * When in doubt, allow the request and log it.
 */
class WSF_Detector {

    use WSF_IP_Utils;

    /**
     * Token cookie name.
     */
    const TOKEN_COOKIE = 'wsf_checkout_token';

    /**
     * Token transient prefix.
     */
    const TOKEN_TRANSIENT_PREFIX = 'wsf_token_';

    /**
     * Rate limit transient prefix.
     */
    const RATE_LIMIT_PREFIX = 'wsf_rate_';

    /**
     * Protected endpoints.
     *
     * @var array
     */
    private static $protected_endpoints = array(
        'wc-ajax=ppc-create-order',
        'wc-ajax=ppc-approve-order',
        'wc-ajax=checkout',
    );

    /**
     * Protected REST API routes.
     *
     * @var array
     */
    private static $protected_rest_routes = array(
        '/wc/store/checkout',
        '/wc/store/v1/checkout',
    );

    /**
     * Initialize the detector.
     */
    public static function init() {
        if ( '1' !== get_option( 'wsf_enabled', '1' ) ) {
            return;
        }

        // Set token on checkout page load.
        add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'inject_token_script' ), 5 );
        add_action( 'woocommerce_checkout_init', array( __CLASS__, 'generate_checkout_token' ) );

        // Also inject on cart page for block-based checkout.
        add_action( 'woocommerce_before_cart', array( __CLASS__, 'inject_token_script' ), 5 );

        // Check AJAX endpoints.
        add_action( 'init', array( __CLASS__, 'check_ajax_request' ), 1 );

        // Check REST API endpoints.
        add_filter( 'rest_pre_dispatch', array( __CLASS__, 'check_rest_request' ), 10, 3 );
    }

    /**
     * Generate a checkout token and store it.
     */
    public static function generate_checkout_token() {
        $token = wp_generate_password( 32, false );
        $ip    = self::get_client_ip();
        $key   = self::TOKEN_TRANSIENT_PREFIX . md5( $ip . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' ) );

        // Store token for 30 minutes.
        set_transient( $key, $token, 30 * MINUTE_IN_SECONDS );

        // Store token in session for JS to pick up.
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( 'wsf_checkout_token', $token );
        }
    }

    /**
     * Inject JavaScript to set the token cookie.
     */
    public static function inject_token_script() {
        $token = '';
        if ( function_exists( 'WC' ) && WC()->session ) {
            $token = WC()->session->get( 'wsf_checkout_token' );
        }

        if ( empty( $token ) ) {
            // Generate token if not exists.
            self::generate_checkout_token();
            if ( function_exists( 'WC' ) && WC()->session ) {
                $token = WC()->session->get( 'wsf_checkout_token' );
            }
        }

        if ( empty( $token ) ) {
            return;
        }

        ?>
        <script type="text/javascript">
            (function() {
                document.cookie = "<?php echo esc_js( self::TOKEN_COOKIE ); ?>=" + "<?php echo esc_js( $token ); ?>" + ";path=/;SameSite=Strict;max-age=1800";
            })();
        </script>
        <?php
    }

    /**
     * Check AJAX requests to protected endpoints.
     */
    public static function check_ajax_request() {
        if ( ! self::is_protected_endpoint() ) {
            return;
        }

        // Only check POST requests.
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        $endpoint = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : 'unknown';
        self::validate_request( $endpoint );
    }

    /**
     * Check if the current request is to a protected endpoint.
     *
     * @return bool
     */
    private static function is_protected_endpoint() {
        $query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';

        foreach ( self::$protected_endpoints as $endpoint ) {
            if ( strpos( $query_string, $endpoint ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check REST API requests to protected routes.
     *
     * @param mixed           $result  Response to replace the requested version with.
     * @param WP_REST_Server  $server  Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     * @return mixed
     */
    public static function check_rest_request( $result, $server, $request ) {
        if ( '1' !== get_option( 'wsf_enabled', '1' ) ) {
            return $result;
        }

        $route = $request->get_route();

        foreach ( self::$protected_rest_routes as $protected_route ) {
            if ( strpos( $route, $protected_route ) !== false && 'POST' === $request->get_method() ) {
                self::validate_request( $route );
                break;
            }
        }

        return $result;
    }

    /**
     * Validate a request using multiple detection layers.
     *
     * @param string $endpoint The endpoint being accessed.
     */
    private static function validate_request( $endpoint ) {
        $ip = self::get_client_ip();

        // Check if IP is whitelisted - always allow.
        if ( self::is_ip_whitelisted( $ip ) ) {
            return;
        }

        $flags     = array();
        $score     = 0;
        $threshold = (int) get_option( 'wsf_block_threshold', 3 );
        $test_mode = '1' === get_option( 'wsf_test_mode', '1' );

        // Layer 1: JavaScript Token Verification (most reliable).
        if ( ! self::has_valid_token( $ip ) ) {
            $flags[] = 'missing_token';
            $score  += 2;
        }

        // Layer 2: WooCommerce Session Check.
        if ( ! self::has_wc_session() ) {
            $flags[] = 'no_wc_session';
            $score  += 1;
        }

        // Layer 3: Rate Limiting Check.
        $rate_status = self::check_rate_limit( $ip );
        if ( 'exceeded' === $rate_status ) {
            $flags[] = 'rate_limit_exceeded';
            $score  += 3;
        }

        // Layer 4: Referer Check (soft signal).
        if ( ! self::has_valid_referer() ) {
            $flags[] = 'invalid_referer';
            $score  += 1;
        }

        // If no flags, request is legitimate - allow it.
        if ( empty( $flags ) ) {
            return;
        }

        // Log the suspicious request.
        $request_data = array(
            'flags'      => $flags,
            'score'      => $score,
            'threshold'  => $threshold,
            'test_mode'  => $test_mode,
            'method'     => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
            'referer'    => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
            'has_token'  => isset( $_COOKIE[ self::TOKEN_COOKIE ] ) ? 'yes' : 'no',
        );

        // Determine if we should block.
        $should_block = $score >= $threshold && ! $test_mode;

        // Always log suspicious requests.
        $reason = implode( ', ', $flags ) . " (score: {$score}/{$threshold})";
        WSF_Logger::log_blocked_request( $endpoint, $reason, $request_data, $should_block ? 'blocked' : 'logged' );

        // Increment rate limit counter.
        self::increment_rate_limit( $ip );

        // Only block if in blocking mode AND score exceeds threshold.
        if ( $should_block ) {
            self::block_request();
        }
    }

    /**
     * Check if the request has a valid token.
     *
     * @param string $ip Client IP address.
     * @return bool
     */
    private static function has_valid_token( $ip ) {
        // Check for token cookie.
        if ( ! isset( $_COOKIE[ self::TOKEN_COOKIE ] ) ) {
            return false;
        }

        $cookie_token = sanitize_text_field( wp_unslash( $_COOKIE[ self::TOKEN_COOKIE ] ) );

        // Verify token against stored transient.
        $key          = self::TOKEN_TRANSIENT_PREFIX . md5( $ip . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' ) );
        $stored_token = get_transient( $key );

        if ( $stored_token && hash_equals( $stored_token, $cookie_token ) ) {
            return true;
        }

        // Fallback: Check WooCommerce session token.
        if ( function_exists( 'WC' ) && WC()->session ) {
            $session_token = WC()->session->get( 'wsf_checkout_token' );
            if ( $session_token && hash_equals( $session_token, $cookie_token ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request has a WooCommerce session.
     *
     * @return bool
     */
    private static function has_wc_session() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            // WooCommerce not loaded yet, give benefit of doubt.
            return true;
        }

        // Check if session has customer ID (set when browsing store).
        $customer_id = WC()->session->get_customer_id();

        return ! empty( $customer_id );
    }

    /**
     * Check rate limit for IP.
     *
     * @param string $ip Client IP address.
     * @return string 'ok' or 'exceeded'
     */
    private static function check_rate_limit( $ip ) {
        $key       = self::RATE_LIMIT_PREFIX . md5( $ip );
        $attempts  = (int) get_transient( $key );
        $max_attempts = (int) get_option( 'wsf_rate_limit', 10 );

        return $attempts >= $max_attempts ? 'exceeded' : 'ok';
    }

    /**
     * Increment rate limit counter for IP.
     *
     * @param string $ip Client IP address.
     */
    private static function increment_rate_limit( $ip ) {
        $key      = self::RATE_LIMIT_PREFIX . md5( $ip );
        $attempts = (int) get_transient( $key );

        // Set/update counter with 1-hour expiry.
        set_transient( $key, $attempts + 1, HOUR_IN_SECONDS );
    }

    /**
     * Check if request has a valid referer.
     *
     * @return bool
     */
    private static function has_valid_referer() {
        if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
            return false;
        }

        $referer  = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
        $site_url = home_url();

        // Check if referer starts with site URL.
        return strpos( $referer, $site_url ) === 0;
    }

    /**
     * Check if IP is whitelisted.
     *
     * @param string $ip Client IP address.
     * @return bool
     */
    private static function is_ip_whitelisted( $ip ) {
        $whitelist = get_option( 'wsf_whitelist_ips', '' );

        if ( empty( $whitelist ) ) {
            return false;
        }

        $ips = array_map( 'trim', explode( ',', $whitelist ) );

        return in_array( $ip, $ips, true );
    }

    /**
     * Block the request with a 403 response.
     */
    private static function block_request() {
        status_header( 403 );
        header( 'Content-Type: application/json; charset=utf-8' );

        echo wp_json_encode(
            array(
                'code'    => 'request_blocked',
                'message' => 'This request has been blocked for security reasons. If you believe this is an error, please contact support.',
                'data'    => array(
                    'status' => 403,
                ),
            )
        );

        exit;
    }
}
