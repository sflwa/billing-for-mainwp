jQuery(document).ready(function ($) {

    // Logic for Manual Mapping on the Mapping Tab

    var mapSite = function(recordId, siteId, button) {
        var ajaxData = {
            action: 'mainwp_billing_map_site',
            record_id: recordId,
            site_id: siteId
        };

        button.addClass('loading');

        $.post(ajaxurl, ajaxData, function(response) {
            button.removeClass('loading');
            var row = button.closest('tr');

            if (response.success) {
                // Get the newly selected site's name for updating the table cell
                var newSiteName = row.find('.mainwp-billing-site-select option[value="' + siteId + '"]').text();

                // Display success checkmark
                row.find('.mainwp-billing-mapped-check').show();
                // Hide button
                button.hide();

                // Update the Mapped Site column (5th cell) with the new site name/link
                var mappedSiteColumn = row.find('td:eq(3)');
                if (siteId > 0) {
                    var siteLink = 'admin.php?page=managesites&dashboard=' + siteId;
                    mappedSiteColumn.html('<a href="' + siteLink + '" target="_blank">' + newSiteName + '</a>');
                } else {
                     mappedSiteColumn.html('<span class="ui red label">Unmapped</span>');
                }

                // Update the dropdown's "original-value" data attribute
                row.find('.mainwp-billing-site-select').data('original-value', siteId);


                setTimeout(function() {
                    row.find('.mainwp-billing-mapped-check').fadeOut();
                }, 2000);

            } else {
                alert('Error mapping site: ' + (response.data.error || 'Unknown error.'));
                // Revert dropdown selection to original value if mapping failed
                var originalValue = row.find('.mainwp-billing-site-select').data('original-value');
                row.find('.mainwp-billing-site-select').dropdown('set selected', originalValue);
            }
        }, 'json');
    };


    // Initialize dropdowns and set original value for comparison
    $('.mainwp-billing-site-select').each(function() {
        var $select = $(this);
        // Store the initial mapped ID as the original value
        $select.data('original-value', $select.val());

        $select.on('change', function() {
            var selectedId = $(this).val();
            var originalId = $select.data('original-value');
            var button = $select.siblings('.mainwp-billing-map-button');

            if (selectedId != originalId) {
                // New site selected, show the 'Map' button
                button.show();
            } else {
                // Selection reverted to original, hide the 'Map' button
                button.hide();
            }
        });

        // Handle button click for manual map
        $select.siblings('.mainwp-billing-map-button').on('click', function(e) {
            e.preventDefault();
            var recordId = $(this).data('record-id');
            var siteId = $select.val();

            mapSite(recordId, siteId, $(this));
        });
    });

    // Initialize Semantic UI dropdown
    $('.ui.dropdown').dropdown();


    // Logic for Clear All Data Button (Import Tab - Req #4)
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
            messageSpan.show();

            if (response.success) {
                messageSpan.addClass('green').text(response.data.message);
                
                // Clear the Last Imported Date display
                button.closest('.ui.segment').find('strong:contains("Last Imported Date:")').next().text('Never imported.');

            } else {
                messageSpan.addClass('red').text('Error: ' + (response.data.error || 'Failed to clear data.'));
            }

            setTimeout(function() {
                messageSpan.fadeOut();
            }, 5000);

        }, 'json');
    });

});
