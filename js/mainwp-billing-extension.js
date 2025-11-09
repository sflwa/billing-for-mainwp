/* MainWP Billing Extension JS - Version 1.7.6 (Loop Fix) */

jQuery(document).ready(function ($) {

    // Global flag to prevent onChange loop during AJAX save
    var isSaving = false;

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

    var mapSite = function(recordId, siteId, dropdownElement) {
        var row = dropdownElement.closest('tr');
        
        console.log("Mapping Record: " + recordId + " to Site ID: " + siteId); // Debugging
        
        // Disable wrapper and show loading indicator
        dropdownElement.addClass('loading disabled');
        isSaving = true; // Set loop protection flag

        var ajaxData = {
            action: 'mainwp_billing_map_site',
            record_id: recordId,
            site_id: siteId
        };

        $.post(ajaxurl, ajaxData, function(response) {
            
            // Re-enable wrapper and clear flag
            dropdownElement.removeClass('loading disabled');
            isSaving = false;
            
            // Handle success response (which is JSON)
            if (response.success) {
                // The new site name is now the text of the selected div.item inside the wrapper
                var newSiteName = dropdownElement.find('.menu .item.active').text().trim();
                
                // Update the Mapped Site column (4th cell in this table)
                var mappedSiteColumn = row.find('td:eq(3)');
                if (siteId > 0 && siteId !== '0') {
                    var siteLink = 'admin.php?page=managesites&dashboard=' + siteId;
                    mappedSiteColumn.html('<a href="' + siteLink + '" target="_blank">' + newSiteName + '</a>');
                    showNotification('success', 'Mapping Saved', 'Record ' + recordId + ' successfully mapped to ' + newSiteName + '.');
                } else {
                     mappedSiteColumn.html('<span class="ui red label">Unmapped</span>');
                     showNotification('info', 'Mapping Cleared', 'Record ' + recordId + ' is now unmapped.');
                }
                
            } else {
                // Handle error response (which should be JSON with an error message)
                var errorMsg = response.data ? (response.data.error || 'Unknown error. Check console.') : 'Server did not return error message.';
                showNotification('error', 'Mapping Failed', 'Could not save mapping. ' + errorMsg);
                
                // If save fails, revert the dropdown visually
                dropdownElement.dropdown('restore defaults');
            }
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            // This handles cases where the response is NOT JSON (e.g., a PHP Fatal Error/Warning corrupted the output)
            dropdownElement.removeClass('loading disabled');
            isSaving = false; // Clear flag even on failure
            console.error("AJAX Error: Status=" + textStatus + ", Error=" + errorThrown);
            console.error("Server Response: ", jqXHR.responseText);
            showNotification('error', 'Critical Error', 'Server error. Check console and PHP logs for details.');
            
            // If connection fails, revert the dropdown visually
            dropdownElement.dropdown('restore defaults');
        });
    };


    // --- Setup and Event Handlers ---
    
    // Initialize only dropdowns that don't need the mapping logic (e.g., filter dropdowns).
    $('.ui.dropdown').not('.mainwp-billing-map-wrapper').dropdown();


    // Initialize the mapping dropdowns separately
    $('.mainwp-billing-map-wrapper').each(function() {
        var $wrapper = $(this);
        
        // Read the recordId from the stable wrapper element
        var recordId = $wrapper.attr('data-record-id'); 
        
        console.log("Processing wrapper. Found recordId:", recordId);

        // Double-check: if recordId is null/empty/undefined, we skip initialization
        if (!recordId) {
            console.error("Skipping dropdown initialization: data-record-id is missing/invalid for element:", this);
            return;
        }
        
        // Initialize this specific dropdown, applying the necessary styling and behavior.
        $wrapper.dropdown({
            // Semantic UI's recommended way to listen for changes
            onChange: function(value, text, $choice) {
                // Only proceed if a save isn't already active
                if (isSaving) {
                    console.warn("Change event suppressed during AJAX save.");
                    return; 
                }

                // value is the new selected value (site ID)
                mapSite(recordId, value, $wrapper);
            }
        });
    });


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
