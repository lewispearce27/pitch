jQuery(document).ready(function ($) {

    // Debug
    console.log('PitchPrint Custom JS loaded');

    // Add container for PitchPrint modal (if not exists)
    if ($('#pitchprint-modal-container').length === 0) {
        $('body').append('<div id="pitchprint-modal-container" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.8); justify-content:center; align-items:center;"><div id="pitchprint-box" style="background:#fff; padding:0; border-radius:10px; position:relative;"><span id="pitchprint-close" style="position:absolute; top:10px; right:20px; cursor:pointer; font-size:22px; font-weight:bold;">&times;</span><div class="pitchprint"></div></div></div>');
    }

    // DESIGN ONLINE
    $(document).on('click', '.ppcustom-design-btn', function (e) {
        e.preventDefault();

        if (!ppcustom.designId || !ppcustom.apiKey) {
            alert('PitchPrint is not configured. Please contact admin.');
            return;
        }

        // Show modal
        $('#pitchprint-modal-container').fadeIn(200);

        // Clean up any previous PitchPrint iframes
        $('#pitchprint-modal-container .pitchprint').empty();

        // Load PitchPrint editor in the modal
        PitchPrint.load({
            apiKey: ppcustom.apiKey,
            designId: ppcustom.designId,
            container: '#pitchprint-modal-container .pitchprint'
        });
    });

    // Close PitchPrint modal
    $(document).on('click', '#pitchprint-close', function () {
        $('#pitchprint-modal-container').fadeOut(200, function () {
            $('.pitchprint').empty();
        });
    });

    // ESC closes modal
    $(document).on('keyup', function (e) {
        if (e.key === "Escape") $('#pitchprint-modal-container').fadeOut(200, function () {
            $('.pitchprint').empty();
        });
    });

    // UPLOAD ARTWORK
    $(document).on('click', '.ppcustom-upload-btn', function (e) {
        e.preventDefault();
        $('#pp-upload-modal').show();
    });

    $(document).on('click', '#pp-upload-close', function () {
        $('#pp-upload-modal').hide();
    });

    // Placeholder upload logic (add real integration later)
    $(document).on('click', '#pp-upload-submit', function () {
        var fileInput = $('#pp-artwork-file')[0];
        if (!fileInput.files.length) {
            alert('Please select a file.');
            return;
        }
        alert('File "' + fileInput.files[0].name + '" selected for upload (integration needed).');
        $('#pp-upload-modal').hide();
    });
});
