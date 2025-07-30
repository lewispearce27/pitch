jQuery(document).ready(function ($) {

    console.log('PitchPrint Custom JS loaded');

    // If PitchPrint config is missing, stop.
    if (typeof ppcustom === 'undefined') {
        console.warn('PitchPrint settings not passed from PHP.');
        return;
    }

    // Function to initialise PitchPrint
    function initPitchPrint() {
        if (typeof PitchPrint === 'undefined') {
            console.warn('PitchPrint SDK not loaded yet.');
            return;
        }
        if (!ppcustom.designId || !ppcustom.apiKey) {
            console.warn('No designId or apiKey provided.');
            return;
        }
        console.log('Initialising PitchPrint with designId:', ppcustom.designId);

        window.pitchprint = new PitchPrint({
            apiKey: ppcustom.apiKey,
            designId: ppcustom.designId
        });
    }

    // Wait for PitchPrint SDK to be loaded
    function waitForPitchPrint(callback, attempts) {
        attempts = attempts || 0;
        if (typeof PitchPrint !== 'undefined') {
            callback();
        } else if (attempts < 20) {
            setTimeout(function () {
                waitForPitchPrint(callback, attempts + 1);
            }, 500);
        } else {
            console.error('PitchPrint SDK failed to load.');
        }
    }

    // Design Online button
    $(document).on('click', '.ppcustom-design-btn', function (e) {
        e.preventDefault();

        console.log('Design Online button clicked');

        waitForPitchPrint(function () {
            initPitchPrint();
            if (typeof window.pitchprint !== 'undefined') {
                window.pitchprint.show();
            }
        });
    });

    // Upload Artwork button (show modal)
    $(document).on('click', '.ppcustom-upload-btn', function (e) {
        e.preventDefault();
        $('#pp-upload-modal').show();
    });

    // Close modal
    $(document).on('click', '#pp-upload-close', function () {
        $('#pp-upload-modal').hide();
    });

    // Upload logic (fake, you will want to add your real upload integration here)
    $(document).on('click', '#pp-upload-submit', function () {
        var fileInput = $('#pp-artwork-file')[0];
        if (!fileInput.files.length) {
            alert('Please select a file.');
            return;
        }
        alert('File "' + fileInput.files[0].name + '" selected for upload (integration needed).');
        $('#pp-upload-modal').hide();
    });

    // Optionally, auto-initialise PitchPrint on page load
    waitForPitchPrint(initPitchPrint);
});

