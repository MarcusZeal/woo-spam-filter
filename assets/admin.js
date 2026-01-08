/**
 * WooCommerce Spam Filter - Admin JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready.
    $(document).ready(function() {
        initChart();
        initCopyButtons();
        initExpandableRows();
        initBulkActions();
    });

    /**
     * Initialize the attack trend chart.
     */
    function initChart() {
        var canvas = document.getElementById('wsf-chart');
        if (!canvas) {
            return;
        }

        // Get chart data from data attribute.
        var chartData = canvas.dataset.chartData;
        if (!chartData) {
            return;
        }

        try {
            var data = JSON.parse(chartData);
        } catch (e) {
            console.error('Failed to parse chart data:', e);
            return;
        }

        var ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Logged',
                        data: data.logged,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Blocked',
                        data: data.blocked,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Initialize copy IP buttons.
     */
    function initCopyButtons() {
        $(document).on('click', '.wsf-copy-ip', function(e) {
            e.preventDefault();
            var ip = $(this).data('ip');
            var button = $(this);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(ip).then(function() {
                    showCopyFeedback(button, true);
                }).catch(function() {
                    fallbackCopy(ip, button);
                });
            } else {
                fallbackCopy(ip, button);
            }
        });
    }

    /**
     * Fallback copy method for older browsers.
     */
    function fallbackCopy(text, button) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showCopyFeedback(button, true);
        } catch (err) {
            showCopyFeedback(button, false);
        }

        document.body.removeChild(textarea);
    }

    /**
     * Show copy feedback.
     */
    function showCopyFeedback(button, success) {
        var originalText = button.text();
        button.text(success ? 'Copied!' : 'Failed');
        button.addClass(success ? 'tw-text-green-600' : 'tw-text-red-600');

        setTimeout(function() {
            button.text(originalText);
            button.removeClass('tw-text-green-600 tw-text-red-600');
        }, 1500);
    }

    /**
     * Initialize expandable log rows.
     */
    function initExpandableRows() {
        $(document).on('click', '.wsf-expand-row', function(e) {
            e.preventDefault();
            var row = $(this).closest('tr');
            var detailsRow = row.next('.wsf-details-row');

            if (detailsRow.length) {
                detailsRow.toggleClass('tw-hidden');
                $(this).find('svg').toggleClass('tw-rotate-180');
            }
        });
    }

    /**
     * Initialize bulk actions.
     */
    function initBulkActions() {
        // Select all checkbox.
        $(document).on('change', '#wsf-select-all', function() {
            $('.wsf-select-row').prop('checked', $(this).is(':checked'));
            updateBulkActionButton();
        });

        // Individual checkbox.
        $(document).on('change', '.wsf-select-row', function() {
            updateBulkActionButton();

            // Update select all state.
            var total = $('.wsf-select-row').length;
            var checked = $('.wsf-select-row:checked').length;
            $('#wsf-select-all').prop('checked', total === checked);
        });

        // Bulk whitelist button.
        $(document).on('click', '#wsf-bulk-whitelist', function(e) {
            e.preventDefault();

            var selectedIPs = [];
            $('.wsf-select-row:checked').each(function() {
                selectedIPs.push($(this).val());
            });

            if (selectedIPs.length === 0) {
                alert('Please select at least one IP address.');
                return;
            }

            if (!confirm('Are you sure you want to whitelist ' + selectedIPs.length + ' IP address(es)?')) {
                return;
            }

            var button = $(this);
            button.prop('disabled', true).text('Processing...');

            $.ajax({
                url: wsfAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wsf_bulk_whitelist',
                    nonce: wsfAdmin.nonce,
                    ips: selectedIPs
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        button.prop('disabled', false).text('Whitelist Selected');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    button.prop('disabled', false).text('Whitelist Selected');
                }
            });
        });
    }

    /**
     * Update bulk action button state.
     */
    function updateBulkActionButton() {
        var checked = $('.wsf-select-row:checked').length;
        var button = $('#wsf-bulk-whitelist');

        if (checked > 0) {
            button.prop('disabled', false).text('Whitelist Selected (' + checked + ')');
        } else {
            button.prop('disabled', true).text('Whitelist Selected');
        }
    }

})(jQuery);
