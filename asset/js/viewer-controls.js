/* Per-viewer fullscreen control for GLB media. */
(function () {
    'use strict';

    function install() {
        document.querySelectorAll('.threedviewer-model-stage').forEach(function (stage) {
            if (stage.dataset.viewerControlsReady === 'true') {
                return;
            }
            stage.dataset.viewerControlsReady = 'true';
            const button = stage.querySelector('.threedviewer-fullscreen button');
            if (!button || typeof stage.requestFullscreen !== 'function') {
                if (button) {
                    button.hidden = true;
                }
                return;
            }

            button.addEventListener('click', function () {
                if (document.fullscreenElement === stage) {
                    document.exitFullscreen();
                } else {
                    stage.requestFullscreen().catch(function () {
                        button.hidden = true;
                    });
                }
            });
        });
        updateButtons();
    }

    function updateButtons() {
        document.querySelectorAll('.threedviewer-model-stage').forEach(function (stage) {
            const button = stage.querySelector('.threedviewer-fullscreen button');
            if (!button) {
                return;
            }
            const expanded = document.fullscreenElement === stage;
            const label = expanded ? button.dataset.exitLabel : button.dataset.enterLabel;
            button.textContent = expanded ? '×' : '⛶';
            button.setAttribute('aria-label', label);
            button.title = label;
        });
    }

    document.addEventListener('fullscreenchange', updateButtons);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install, { once: true });
    } else {
        install();
    }
}());
