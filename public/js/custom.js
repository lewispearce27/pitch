jQuery(document).ready(function($) {
    // Design Online button
    $('#ppcustom-design-online').click(function(e) {
        e.preventDefault();
        var templateId = $(this).data('template');
        if (typeof PitchPrint !== 'undefined') {
            PitchPrint.load({
                pid: templateId
            });
        } else {
            alert('PitchPrint SDK failed to load.');
        }
    });

    // Upload Artwork button
    $('#ppcustom-upload-btn').click(function(e) {
        e.preventDefault();
        $('#ppcustom-upload-artwork').trigger('click');
    });
    $('#ppcustom-upload-artwork').change(function() {
        var file = this.files[0];
        if (file) {
            $('#ppcustom-upload-status').text('File "' + file.name + '" selected for upload (integration needed).');
            // Here you would insert the PitchPrint integration code to upload the artwork to the blank design/template.
        }
    });
});
