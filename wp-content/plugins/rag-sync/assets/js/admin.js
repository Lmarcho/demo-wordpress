/**
 * RAG Sync Admin JavaScript
 */

(function($) {
    'use strict';

    const RAGSyncAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $('#rag-sync-test-connection').on('click', this.testConnection.bind(this));
            $('#rag-sync-full-sync').on('click', this.triggerFullSync.bind(this));
            $('.copy-btn').on('click', this.copyToClipboard.bind(this));
            $('#rag-sync-auto-endpoint').on('click', this.useDefaultEndpoint.bind(this));
        },

        /**
         * Use default endpoint
         */
        useDefaultEndpoint: function(e) {
            e.preventDefault();
            const endpoint = $(e.currentTarget).data('endpoint');
            $('#rag_sync_webhook_endpoint').val(endpoint);
            $(e.currentTarget).hide();
        },

        /**
         * Test connection to backend
         */
        testConnection: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const originalText = $button.text();

            this.setLoading($button, ragSyncAdmin.strings.testing);

            $.ajax({
                url: ragSyncAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rag_sync_test_connection',
                    nonce: ragSyncAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(response.data.message, 'success');
                    } else {
                        this.showMessage(response.data.message, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('Connection failed: ' + error, 'error');
                },
                complete: () => {
                    this.clearLoading($button, originalText);
                }
            });
        },

        /**
         * Trigger full sync
         */
        triggerFullSync: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to trigger a full sync? This may take a while.')) {
                return;
            }

            const $button = $(e.currentTarget);
            const originalText = $button.text();

            this.setLoading($button, ragSyncAdmin.strings.syncing);
            this.updateStatus('syncing');

            $.ajax({
                url: ragSyncAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rag_sync_trigger_full_sync',
                    nonce: ragSyncAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(response.data.message, 'success');
                        this.updateStatus('idle');
                        this.updateLastSync('Just now');
                    } else {
                        this.showMessage(response.data.message, 'error');
                        this.updateStatus('error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage('Sync failed: ' + error, 'error');
                    this.updateStatus('error');
                },
                complete: () => {
                    this.clearLoading($button, originalText);
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const targetId = $button.data('target');
            const $target = $('#' + targetId);
            const text = $target.text();

            navigator.clipboard.writeText(text).then(() => {
                const originalText = $button.text();
                $button.text('Copied!');
                setTimeout(() => {
                    $button.text(originalText);
                }, 2000);
            }).catch((err) => {
                console.error('Failed to copy:', err);
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                const originalText = $button.text();
                $button.text('Copied!');
                setTimeout(() => {
                    $button.text(originalText);
                }, 2000);
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            const $messageBox = $('#rag-sync-message');
            $messageBox
                .removeClass('success error info')
                .addClass(type)
                .html(message)
                .slideDown();

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $messageBox.slideUp();
                }, 5000);
            }
        },

        /**
         * Update status display
         */
        updateStatus: function(status) {
            const $status = $('#rag-sync-status');
            $status
                .removeClass('status-idle status-syncing status-error')
                .addClass('status-' + status)
                .text(status.charAt(0).toUpperCase() + status.slice(1));
        },

        /**
         * Update last sync display
         */
        updateLastSync: function(text) {
            $('#rag-sync-last-sync').text(text);
        },

        /**
         * Set button loading state
         */
        setLoading: function($button, text) {
            $button
                .prop('disabled', true)
                .addClass('rag-sync-loading')
                .data('original-text', $button.text())
                .text(text);
        },

        /**
         * Clear button loading state
         */
        clearLoading: function($button, originalText) {
            $button
                .prop('disabled', false)
                .removeClass('rag-sync-loading')
                .text(originalText || $button.data('original-text'));
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        RAGSyncAdmin.init();
    });

})(jQuery);
