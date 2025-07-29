jQuery(function($) {
    $('#_ppcustom_category_id').on('change', function() {
        var categoryId = $(this).val();
        var designSelect = $('#_ppcustom_design_id');
        designSelect.html('<option value="">Loading...</option>');

        if (!categoryId) {
            designSelect.html('<option value="">Select a design…</option>');
            return;
        }

        $.post(ppcustom_admin.ajaxUrl, {
            action: 'ppcustom_fetch_designs',
            categoryId: categoryId
        }, function(response) {
            designSelect.html('<option value="">Select a design…</option>');

            // Check for data.items structure
            if (response.data && response.data.items && response.data.items.length > 0) {
                response.data.items.forEach(function(design) {
                    var selected = (design.id === ppcustom_admin.selectedDesign) ? 'selected' : '';
                    designSelect.append(
                        '<option value="' + design.id + '" ' + selected + '>' + design.title + '</option>'
                    );
                });
            } else {
                designSelect.html('<option value="">No designs found</option>');
            }
        });
    });

    // Trigger on page load if a category was previously saved
    if ($('#_ppcustom_category_id').val()) {
        $('#_ppcustom_category_id').trigger('change');
    }
});
