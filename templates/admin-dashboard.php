<?php
/**
 * Admin dashboard tab template.
 *
 * @package WooSpamFilter
 *
 * @var array  $stats      Statistics array.
 * @var array  $chart_data Chart data array.
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Dashboard -->
<div class="tw-grid tw-grid-cols-1 tw-gap-5 sm:tw-grid-cols-2 lg:tw-grid-cols-4">
    <!-- Stats cards -->
    <div class="tw-bg-white tw-overflow-hidden tw-shadow tw-rounded-lg">
        <div class="tw-p-5">
            <div class="tw-flex tw-items-center">
                <div class="tw-flex-shrink-0">
                    <svg class="tw-h-6 tw-w-6 tw-text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div class="tw-ml-5 tw-w-0 tw-flex-1">
                    <dl>
                        <dt class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Logged Today</dt>
                        <dd class="tw-text-lg tw-font-semibold tw-text-gray-900"><?php echo esc_html( number_format( $stats['logged_today'] ) ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="tw-bg-white tw-overflow-hidden tw-shadow tw-rounded-lg">
        <div class="tw-p-5">
            <div class="tw-flex tw-items-center">
                <div class="tw-flex-shrink-0">
                    <svg class="tw-h-6 tw-w-6 tw-text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div class="tw-ml-5 tw-w-0 tw-flex-1">
                    <dl>
                        <dt class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Blocked Today</dt>
                        <dd class="tw-text-lg tw-font-semibold tw-text-gray-900"><?php echo esc_html( number_format( $stats['blocked_today'] ) ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="tw-bg-white tw-overflow-hidden tw-shadow tw-rounded-lg">
        <div class="tw-p-5">
            <div class="tw-flex tw-items-center">
                <div class="tw-flex-shrink-0">
                    <svg class="tw-h-6 tw-w-6 tw-text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="tw-ml-5 tw-w-0 tw-flex-1">
                    <dl>
                        <dt class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Flagged This Week</dt>
                        <dd class="tw-text-lg tw-font-semibold tw-text-gray-900"><?php echo esc_html( number_format( $stats['logged_week'] + $stats['blocked_week'] ) ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="tw-bg-white tw-overflow-hidden tw-shadow tw-rounded-lg">
        <div class="tw-p-5">
            <div class="tw-flex tw-items-center">
                <div class="tw-flex-shrink-0">
                    <svg class="tw-h-6 tw-w-6 tw-text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <div class="tw-ml-5 tw-w-0 tw-flex-1">
                    <dl>
                        <dt class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Unique IPs</dt>
                        <dd class="tw-text-lg tw-font-semibold tw-text-gray-900"><?php echo esc_html( number_format( $stats['unique_ips'] ) ); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="tw-mt-8 tw-bg-white tw-shadow tw-rounded-lg tw-p-6">
    <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-4">Attack Trend (Last 14 Days)</h3>
    <div style="height: 300px;">
        <canvas id="wsf-chart" data-chart-data="<?php echo esc_attr( wp_json_encode( $chart_data ) ); ?>"></canvas>
    </div>
</div>

<!-- How it works card -->
<div class="tw-mt-8 tw-bg-white tw-shadow tw-rounded-lg tw-p-6">
    <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-4">How Detection Works</h3>
    <div class="tw-prose tw-prose-sm tw-text-gray-600">
        <p>This plugin uses multiple layers to detect bot attacks while ensuring <strong>zero false positives</strong> for legitimate customers:</p>
        <ol class="tw-mt-3 tw-space-y-2">
            <li><strong>JavaScript Token</strong> - When a customer visits your checkout page, a unique token is set. Bots hitting the API directly won't have this token.</li>
            <li><strong>WooCommerce Session</strong> - Legitimate customers have a WC session from browsing your store. API-only bots don't.</li>
            <li><strong>Rate Limiting</strong> - IPs making more than <?php echo esc_html( get_option( 'wsf_rate_limit', 10 ) ); ?> checkout attempts per hour are flagged.</li>
            <li><strong>Referer Validation</strong> - Requests should come from your own website.</li>
        </ol>
        <p class="tw-mt-3">Each signal adds to a risk score. Only requests exceeding the threshold (<?php echo esc_html( get_option( 'wsf_block_threshold', 3 ) ); ?> points) are blocked.</p>
    </div>
</div>
