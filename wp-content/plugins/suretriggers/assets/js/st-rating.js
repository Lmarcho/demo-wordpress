/**
 * SureTriggers Rating JavaScript
 * 
 * Handles rating link clicks and hiding functionality
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle rating link click
        $('.suretriggers-rating-link').on('click', function(e) {
            // Don't prevent the default action immediately
            // Let the link open first, then handle our logic
            
            var $wrapper = $('#suretriggers-rating-wrapper');
            var self = this;
            
            // Add a small delay to allow the link to open
            setTimeout(function() {
                // Send AJAX request to mark rating as clicked
                $.ajax({
                    url: suretriggers_rating_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'suretriggers_rating_clicked',
                        nonce: suretriggers_rating_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Hide the rating with a smooth fade out
                            $wrapper.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    },
                    error: function() {
                        // Silent fail - don't show error to user
                        console.log('Failed to mark rating as clicked');
                    }
                });
            }, 100); // Small delay to ensure link opens first
        });
    });

})(jQuery);