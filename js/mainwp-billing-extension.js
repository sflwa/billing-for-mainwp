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

    var mapSite = function(recordId, siteId, dropdownElement) {
        var row = dropdownElement.closest('tr');
        
        console.log("Mapping Record: " + recordId + " to Site ID: " + siteId); // Debugging
        
        // Disable dropdown and show loading indicator
        dropdownElement.addClass('loading disabled');

        var ajaxData = {
            action: 'mainwp_billing_map_site',
            record_id: recordId,
            site_id: siteId
        };

        $.post(ajaxurl, ajaxData, function(response) {
            
            // Re-enable dropdown
            dropdownElement.removeClass('loading disabled');
            
            if (response.success) {
                var newSiteName = dropdownElement.find('option[value="' + siteId + '"]').text();
                
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
                var errorMsg = response.data.error || 'Unknown error. Check console.';
                showNotification('error', 'Mapping Failed', 'Could not save mapping. ' + errorMsg);
                
                // If save fails, rely on Semantic UI to hold the selected value until page refresh
            }
        }, 'json');
    };


    // --- Setup and Event Handlers ---

    // Initialize all dropdowns generally
    $('.ui.dropdown').dropdown();

    // Initialize the mapping dropdowns separately to attach the onChange handler for auto-save
    $('.mainwp-billing-site-select').each(function() {
        var $select = $(this);
        // FIX: Capture the recordId immediately outside the onChange closure to avoid scoping issues.
        var recordId = $select.data('record-id'); 

        // Initialize the specific dropdown with the Semantic UI onChange callback
        $select.dropdown({
            // Semantic UI's recommended way to listen for changes
            onChange: function(value, text, $choice) {
                // value is the new selected value (site ID)
                
                // Use the captured recordId from the outer scope
                
                // Call the mapping function for auto-save
                mapSite(recordId, value, $select);
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
