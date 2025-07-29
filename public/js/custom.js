jQuery(document).ready(function ($) {

    // Debug log
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

        // Ensure we have a design ID and API key
        if (!ppcustom.designId || !ppcustom.apiKey) {
            console.warn('No designId or apiKey provided.');
            return;
        }

        console.log('Initialising PitchPrint with designId:', ppcustom.designId);

        // Configure PitchPrint
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

    // Bind to the Design Online button
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

    // Optionally, auto-initialise PitchPrint on page load
    waitForPitchPrint(initPitchPrint);
});
