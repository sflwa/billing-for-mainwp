/* MainWP Billing Extension JS - Version 1.7.7 (Button Click Logic) */

jQuery(document).ready(function ($) {

    // --- Notification Logic ---
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


    // --- Manual Mapping Logic (Mapping Tab) ---

    // Bind the mapping function to the click of the Update Mapping button
    $('.mainwp-billing-map-button').on('click', function() {
        var button = $(this);
        var row = button.closest('tr');
        
        // Retrieve IDs from HTML attributes
        var recordId = row.data('record-id');
        var siteSelect = row.find('.mainwp-billing-site-select');
        var siteId = siteSelect.val();
        var siteName = siteSelect.find('option:selected').text();
        
        console.log("Attempting to map Record: " + recordId + " to Site ID: " + siteId);
        
        // Disable button and select box, show loading
        button.addClass('loading disabled');
        siteSelect.addClass('disabled');


        var ajaxData = {
            action: 'mainwp_billing_map_site',
            record_id: recordId,
            site_id: siteId
        };

        $.post(ajaxurl, ajaxData, function(response) {
            
            // Re-enable elements
            button.removeClass('loading disabled');
            siteSelect.removeClass('disabled');
            
            // Handle success response (which is JSON)
            if (response.success) {
                
                // Find the Mapped Site column (4th cell in the table)
                var mappedSiteColumn = row.find('td:eq(3)');
                
                if (siteId > 0 && siteId !== '0') {
                    // Update the link in the Mapped Site column
                    var siteLink = 'admin.php?page=managesites&dashboard=' + siteId;
                    mappedSiteColumn.html('<a class="mapped-site-link" href="' + siteLink + '" target="_blank">' + siteName + '</a>');
                    showNotification('success', 'Mapping Saved', 'Record ' + recordId + ' successfully mapped to ' + siteName + '.');
                } else {
                     // Update to Unmapped
                     mappedSiteColumn.html('<span class="ui red label">Unmapped</span>');
                     showNotification('info', 'Mapping Cleared', 'Record ' + recordId + ' is now unmapped.');
                }
                
                // Visually confirm update by temporarily disabling the button
                button.addClass('green disabled').text('Updated');
                setTimeout(function() {
                    button.removeClass('green disabled').text('Update Mapping');
                }, 2000);

                
            } else {
                // Handle error response (which should be JSON with an error message)
                var errorMsg = response.data ? (response.data.error || 'Unknown error. Check console.') : 'Server did not return error message.';
                showNotification('error', 'Mapping Failed', 'Could not save mapping. ' + errorMsg);
                button.text('Failed');
                
            }
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            // This handles cases where the response is NOT JSON (e.g., a PHP Fatal Error/Warning corrupted the output)
            button.removeClass('loading disabled').text('Error');
            siteSelect.removeClass('disabled');
            console.error("AJAX Error: Status=" + textStatus + ", Error=" + errorThrown);
            console.error("Server Response: ", jqXHR.responseText);
            showNotification('error', 'Critical Error', 'Server error. Check console and PHP logs for details.');
        });
    });
    
    // Initialize Semantic UI dropdowns for all select elements (including filters and mapping selects)
    $('.ui.dropdown').dropdown();


    // --- Clear All Data Button Logic (Import Tab) ---
    $('#mainwp-billing-clear-data-button').on('click', function() {
        if (!confirm('Are you sure you want to permanently delete ALL imported billing data? This action cannot be undone.')) {
            return;
        }

        var button = $(this);
        
        button.addClass('loading');

        var ajaxData = {
            action: 'mainwp_billing_clear_data',
        };

        $.post(ajaxurl, ajaxData, function(response) {
            button.removeClass('loading');
            
            if (response.success) {
                showNotification('success', 'Data Cleared', response.data.message);
                
                // Clear the Last Imported Date display
                button.closest('.ui.segment').find('strong:contains("Last Imported Date:")').next().text('Never imported.');

            } else {
                showNotification('error', 'Clear Failed', response.data.error || 'Failed to clear data.');
            }

        }, 'json');
    });

});
