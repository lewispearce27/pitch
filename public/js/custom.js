jQuery(document).ready(function($) {

    $('.pp-design-online').on('click', function() {
        if (!ppcustom.designId) {
            alert('No PitchPrint template is set for this product.');
            return;
        }
        new PitchPrintClient({
            apiKey: ppcustom.apiKey,
            designId: ppcustom.designId,
            mode: 'popup'
        });
    });

    $('.pp-upload-artwork').on('click', function() {
        $('#pp-upload-modal').show();
    });

    $('#pp-upload-submit').on('click', function() {
        const file = $('#pp-file-input')[0].files[0];
        if (!file) { alert('Please select a file'); return; }

        const formData = new FormData();
        formData.append('file', file);

        $.ajax({
            url: ppcustom.ajaxUrl + '?action=ppcustom_upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (!response.url) {
                    alert('Upload failed');
                    return;
                }
                new PitchPrintClient({
                    apiKey: ppcustom.apiKey,
                    designId: ppcustom.designId,
                    mode: 'popup',
                    images: [response.url]
                });
                $('#pp-upload-modal').hide();
            }
        });
    });

});
