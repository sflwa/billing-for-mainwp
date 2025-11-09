/* MainWP Billing Extension JS - Version 1.8.1 (Cleaned: Non-AJAX Mapping) */

jQuery(document).ready(function ($) {

    // --- Notification Logic (Removed as not used by mapping forms) ---
    // NOTE: Notification logic is kept here for other potential AJAX actions.
    var showNotification = function(type, header, message) {
        var notification = $('.mainwp-billing-notification');
        
        // Ensure notification is visible before styling/content update
        notification.stop(true, true);

        // Reset classes and set content
        notification.removeClass('success error info');
        notification.addClass(type);
        notification.find('.header').text(header);
        notification.find('p').text(message);

        notification.fadeIn(300).css('display', 'block');

        // Auto-close after 3 seconds
        setTimeout(function() {
            notification.fadeOut(500);
        }, 3000);
    };

    // Close button handler for notification
    $('.mainwp-billing-notification .close.icon').on('click', function() {
        $(this).closest('.mainwp-billing-notification').fadeOut(500);
    });

    
    // Initialize Semantic UI dropdowns for all select elements
    // All functionality is now handled by PHP POST submission.
    $('.ui.dropdown').dropdown();

});
