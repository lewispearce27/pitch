jQuery(function($) {
    $('.pp-design-online').on('click', function() {
        var designId = ppcustom.designId;
        var apiKey = ppcustom.apiKey;
        if (!designId || !apiKey) {
            alert('No design selected for this product.');
            return;
        }

        // Launch PitchPrint
        if (typeof PitchPrint !== 'undefined') {
            PitchPrint.create({
                apiKey: apiKey,
                designId: designId,
                mode: 'modal'
            });
        } else {
            alert('PitchPrint SDK not loaded.');
        }
    });

    $('.pp-upload-artwork').on('click', function() {
        $('#pp-upload-modal').show();
    });
});
