<?php
/**
 * Admin logs tab template.
 *
 * @package WooSpamFilter
 *
 * @var array  $logs          Array of log entries.
 * @var int    $total         Total count of logs.
 * @var int    $page          Current page number.
 * @var int    $per_page      Items per page.
 * @var string $status_filter Current status filter.
 * @var string $ip_search     Current IP search term.
 * @var string $date_from     Filter from date.
 * @var string $date_to       Filter to date.
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Logs -->
<div class="tw-bg-white tw-shadow tw-rounded-lg">
    <!-- Filters -->
    <div class="tw-px-4 tw-py-4 tw-border-b tw-border-gray-200">
        <form method="get" class="tw-flex tw-flex-wrap tw-gap-4 tw-items-end">
            <input type="hidden" name="page" value="woo-spam-filter">
            <input type="hidden" name="tab" value="logs">

            <div>
                <label class="tw-block tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">Status</label>
                <select name="status" class="tw-block tw-w-32 tw-rounded-md tw-border-gray-300 tw-shadow-sm tw-text-sm tw-border tw-p-1.5">
                    <option value="all" <?php selected( 'all', $status_filter ); ?>>All</option>
                    <option value="logged" <?php selected( 'logged', $status_filter ); ?>>Logged</option>
                    <option value="blocked" <?php selected( 'blocked', $status_filter ); ?>>Blocked</option>
                </select>
            </div>

            <div>
                <label class="tw-block tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">IP Address</label>
                <input type="text" name="ip_search" value="<?php echo esc_attr( $ip_search ); ?>" placeholder="Search IP..."
                       class="tw-block tw-w-36 tw-rounded-md tw-border-gray-300 tw-shadow-sm tw-text-sm tw-border tw-p-1.5">
            </div>

            <div>
                <label class="tw-block tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>"
                       class="tw-block tw-rounded-md tw-border-gray-300 tw-shadow-sm tw-text-sm tw-border tw-p-1.5">
            </div>

            <div>
                <label class="tw-block tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>"
                       class="tw-block tw-rounded-md tw-border-gray-300 tw-shadow-sm tw-text-sm tw-border tw-p-1.5">
            </div>

            <div>
                <button type="submit" class="tw-bg-indigo-600 tw-text-white tw-px-4 tw-py-1.5 tw-rounded-md tw-text-sm tw-font-medium hover:tw-bg-indigo-700">
                    Filter
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=logs' ) ); ?>"
                   class="tw-ml-2 tw-text-gray-600 tw-text-sm hover:tw-text-gray-900">Reset</a>
            </div>
        </form>
    </div>

    <!-- Actions bar -->
    <div class="tw-px-4 tw-py-3 tw-flex tw-flex-wrap tw-justify-between tw-items-center tw-gap-4 tw-border-b tw-border-gray-200 tw-bg-gray-50">
        <div class="tw-flex tw-items-center tw-space-x-4">
            <span class="tw-text-sm tw-text-gray-600"><?php echo esc_html( number_format( $total ) ); ?> results</span>
            <button type="button" id="wsf-bulk-whitelist" disabled
                    class="tw-inline-flex tw-items-center tw-px-3 tw-py-1.5 tw-border tw-border-gray-300 tw-shadow-sm tw-text-xs tw-font-medium tw-rounded-md tw-text-gray-700 tw-bg-white hover:tw-bg-gray-50 disabled:tw-opacity-50 disabled:tw-cursor-not-allowed">
                Whitelist Selected
            </button>
        </div>
        <div class="tw-flex tw-space-x-3">
            <?php
            $export_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'wsf_export'    => '1',
                        'export_status' => $status_filter,
                        'date_from'     => $date_from,
                        'date_to'       => $date_to,
                    ),
                    admin_url( 'admin.php?page=woo-spam-filter&tab=logs' )
                ),
                'wsf_export_nonce'
            );
            ?>
            <a href="<?php echo esc_url( $export_url ); ?>"
               class="tw-inline-flex tw-items-center tw-px-3 tw-py-1.5 tw-border tw-border-gray-300 tw-shadow-sm tw-text-xs tw-font-medium tw-rounded-md tw-text-gray-700 tw-bg-white hover:tw-bg-gray-50">
                <svg class="tw--ml-0.5 tw-mr-1.5 tw-h-4 tw-w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </a>
            <form method="post" class="tw-inline">
                <?php wp_nonce_field( 'wsf_clear_logs_nonce' ); ?>
                <button type="submit" name="wsf_clear_logs" value="1"
                        onclick="return confirm('Are you sure you want to clear all logs?');"
                        class="tw-inline-flex tw-items-center tw-px-3 tw-py-1.5 tw-border tw-border-red-300 tw-shadow-sm tw-text-xs tw-font-medium tw-rounded-md tw-text-red-700 tw-bg-white hover:tw-bg-red-50">
                    <svg class="tw--ml-0.5 tw-mr-1.5 tw-h-4 tw-w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Clear Logs
                </button>
            </form>
        </div>
    </div>

    <?php if ( empty( $logs ) ) : ?>
        <div class="tw-px-4 tw-py-12 tw-text-center">
            <svg class="tw-mx-auto tw-h-12 tw-w-12 tw-text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="tw-mt-2 tw-text-sm tw-font-medium tw-text-gray-900">No requests found</h3>
            <p class="tw-mt-1 tw-text-sm tw-text-gray-500">No suspicious requests match your filters.</p>
        </div>
    <?php else : ?>
        <div class="tw-overflow-x-auto">
            <table class="tw-min-w-full tw-divide-y tw-divide-gray-200">
                <thead class="tw-bg-gray-50">
                    <tr>
                        <th class="tw-px-3 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">
                            <input type="checkbox" id="wsf-select-all" class="tw-rounded tw-border-gray-300">
                        </th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Date</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Status</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">IP / Country</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Endpoint</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Flags</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left tw-text-xs tw-font-medium tw-text-gray-500 tw-uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="tw-bg-white tw-divide-y tw-divide-gray-200">
                    <?php foreach ( $logs as $log ) : ?>
                        <?php $request_data = json_decode( $log->request_data, true ); ?>
                        <tr class="hover:tw-bg-gray-50">
                            <td class="tw-px-3 tw-py-3">
                                <input type="checkbox" class="wsf-select-row tw-rounded tw-border-gray-300" value="<?php echo esc_attr( $log->ip_address ); ?>">
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm tw-text-gray-500">
                                <?php echo esc_html( gmdate( 'M j, g:i a', strtotime( $log->created_at ) ) ); ?>
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-whitespace-nowrap">
                                <?php if ( 'blocked' === $log->status ) : ?>
                                    <span class="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-red-100 tw-text-red-800">Blocked</span>
                                <?php else : ?>
                                    <span class="tw-px-2 tw-inline-flex tw-text-xs tw-leading-5 tw-font-semibold tw-rounded-full tw-bg-yellow-100 tw-text-yellow-800">Logged</span>
                                <?php endif; ?>
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-whitespace-nowrap">
                                <div class="tw-flex tw-items-center tw-space-x-2">
                                    <?php if ( ! empty( $log->country_code ) ) : ?>
                                        <span title="<?php echo esc_attr( WSF_Logger::get_country_name( $log->country_code ) ); ?>"><?php echo esc_html( WSF_Admin::get_flag_emoji( $log->country_code ) ); ?></span>
                                    <?php endif; ?>
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-900"><?php echo esc_html( $log->ip_address ); ?></span>
                                    <button type="button" class="wsf-copy-ip tw-text-xs tw-text-indigo-600 hover:tw-text-indigo-900" data-ip="<?php echo esc_attr( $log->ip_address ); ?>">Copy</button>
                                </div>
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-500 tw-max-w-xs tw-truncate" title="<?php echo esc_attr( $log->endpoint ); ?>">
                                <?php echo esc_html( $log->endpoint ); ?>
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-text-sm tw-text-gray-500">
                                <?php echo esc_html( $log->blocked_reason ); ?>
                            </td>
                            <td class="tw-px-4 tw-py-3 tw-whitespace-nowrap tw-text-sm">
                                <div class="tw-flex tw-items-center tw-space-x-3">
                                    <button type="button" class="wsf-expand-row tw-text-gray-400 hover:tw-text-gray-600">
                                        <svg class="tw-h-5 tw-w-5 tw-transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <form method="post" class="tw-inline">
                                        <?php wp_nonce_field( 'wsf_whitelist_ip_nonce' ); ?>
                                        <input type="hidden" name="wsf_ip" value="<?php echo esc_attr( $log->ip_address ); ?>">
                                        <button type="submit" name="wsf_whitelist_ip" value="1" class="tw-text-indigo-600 hover:tw-text-indigo-900 tw-text-sm tw-font-medium">Whitelist</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <!-- Expandable details row -->
                        <tr class="wsf-details-row tw-hidden tw-bg-gray-50">
                            <td colspan="7" class="tw-px-4 tw-py-4">
                                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4 tw-text-sm">
                                    <div>
                                        <strong class="tw-text-gray-700">User Agent:</strong>
                                        <p class="tw-text-gray-500 tw-mt-1 tw-break-all"><?php echo esc_html( $log->user_agent ?: 'N/A' ); ?></p>
                                    </div>
                                    <div>
                                        <strong class="tw-text-gray-700">Request Data:</strong>
                                        <pre class="tw-text-gray-500 tw-mt-1 tw-text-xs tw-bg-gray-100 tw-p-2 tw-rounded tw-overflow-auto tw-max-h-32"><?php echo esc_html( wp_json_encode( $request_data, JSON_PRETTY_PRINT ) ); ?></pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php
        $total_pages = ceil( $total / $per_page );
        if ( $total_pages > 1 ) :
            ?>
            <div class="tw-bg-white tw-px-4 tw-py-3 tw-flex tw-items-center tw-justify-between tw-border-t tw-border-gray-200 sm:tw-px-6">
                <div class="tw-hidden sm:tw-flex-1 sm:tw-flex sm:tw-items-center sm:tw-justify-between">
                    <div>
                        <p class="tw-text-sm tw-text-gray-700">
                            Showing <span class="tw-font-medium"><?php echo esc_html( ( ( $page - 1 ) * $per_page ) + 1 ); ?></span>
                            to <span class="tw-font-medium"><?php echo esc_html( min( $page * $per_page, $total ) ); ?></span>
                            of <span class="tw-font-medium"><?php echo esc_html( $total ); ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="tw-relative tw-z-0 tw-inline-flex tw-rounded-md tw-shadow-sm tw--space-x-px">
                            <?php if ( $page > 1 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>" class="tw-relative tw-inline-flex tw-items-center tw-px-2 tw-py-2 tw-rounded-l-md tw-border tw-border-gray-300 tw-bg-white tw-text-sm tw-font-medium tw-text-gray-500 hover:tw-bg-gray-50">
                                        <svg class="tw-h-5 tw-w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max( 1, $page - 2 );
                            $end_page   = min( $total_pages, $page + 2 );

                            for ( $i = $start_page; $i <= $end_page; $i++ ) :
                                ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"
                                   class="<?php echo $i === $page ? 'tw-z-10 tw-bg-indigo-50 tw-border-indigo-500 tw-text-indigo-600' : 'tw-bg-white tw-border-gray-300 tw-text-gray-500 hover:tw-bg-gray-50'; ?> tw-relative tw-inline-flex tw-items-center tw-px-4 tw-py-2 tw-border tw-text-sm tw-font-medium">
                                    <?php echo esc_html( $i ); ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ( $page < $total_pages ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>" class="tw-relative tw-inline-flex tw-items-center tw-px-2 tw-py-2 tw-rounded-r-md tw-border tw-border-gray-300 tw-bg-white tw-text-sm tw-font-medium tw-text-gray-500 hover:tw-bg-gray-50">
                                        <svg class="tw-h-5 tw-w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
