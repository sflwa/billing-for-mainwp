jQuery(document).ready(function ($) {

    // --- Notification Logic ---
    var showNotification = function(type, header, message) {
        var notification = $('.mainwp-billing-notification');
        
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
        var checkmark = row.find('.mainwp-billing-mapped-check');
        var originalSiteName = row.find('td:eq(3)').text();
        
        // Disable dropdown and show loading indicator
        dropdownElement.addClass('loading disabled');
        checkmark.hide(); 

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
                
                // Update the dropdown's "original-value" data attribute
                dropdownElement.data('original-value', siteId);

                // Update the Mapped Site column (4th cell in this table)
                var mappedSiteColumn = row.find('td:eq(3)');
                if (siteId > 0) {
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
                
                // Revert dropdown selection to original value if mapping failed
                var originalValue = dropdownElement.data('original-value');
                dropdownElement.dropdown('set selected', originalValue);
            }
        }, 'json');
    };


    // --- Setup and Event Handlers ---

    // Initialize Semantic UI dropdowns
    $('.ui.dropdown').dropdown();

    // Store initial mapped value and attach change listener for auto-save
    $('.mainwp-billing-site-select').each(function() {
        var $select = $(this);
        
        // Store the initial mapped ID as the original value
        $select.data('original-value', $select.val());

        $select.on('change', function() {
            var selectedId = $(this).val();
            var originalId = $select.data('original-value');
            
            // Only trigger save if the value has genuinely changed
            if (selectedId != originalId) {
                var recordId = $(this).data('record-id');
                mapSite(recordId, selectedId, $(this));
            }
        });
    });

    // --- Clear All Data Button Logic (Import Tab) ---
    $('#mainwp-billing-clear-data-button').on('click', function() {
        if (!confirm('Are you sure you want to permanently delete ALL imported billing data? This action cannot be undone.')) {
            return;
        }

        var button = $(this);
        var messageSpan = $('#mainwp-billing-clear-message');

        button.addClass('loading');
        messageSpan.hide().removeClass('green red').text('');

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
