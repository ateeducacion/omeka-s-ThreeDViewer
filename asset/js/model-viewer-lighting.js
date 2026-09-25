/**
 * Viewer-fixed lighting for <model-viewer>
 *
 * model-viewer lights a model with an environment map fixed in the scene while camera-controls
 * orbit the camera, so the lighting stays with the model. For data-lighting-mode="viewer" this
 * rotates the environment with the camera orbit, so the model turns under the lights instead.
 */
(function () {
  'use strict';

  /**
   * model-viewer has no public accessor for its three.js scene, but hands it to an effect composer
   * on registration. Unregister straight away so model-viewer keeps rendering as normal.
   */
  function getScene(viewer) {
    if (typeof viewer.registerEffectComposer !== 'function') {
      return null;
    }
    let scene = null;
    viewer.registerEffectComposer({
      setRenderer: function () {},
      setMainCamera: function () {},
      setMainScene: function (mainScene) {
        scene = mainScene;
      }
    });
    viewer.unregisterEffectComposer();
    return scene;
  }

  function attach(viewer) {
    if (viewer.dataset.lightingAttached) {
      return;
    }
    const scene = getScene(viewer);
    // Scene.environmentRotation was added in three.js r162.
    if (!scene || !scene.environmentRotation) {
      console.warn('ThreeDViewer: viewer-fixed lighting is not supported by this model-viewer version.');
      return;
    }
    viewer.dataset.lightingAttached = 'true';

    // The first view keeps its lighting; orbiting away rotates the environment by the same amount.
    let initial = null;

    function update() {
      if (!initial) {
        return;
      }
      const orbit = viewer.getCameraOrbit();
      // Camera turn is Ry(theta) Rx(phi - phi0); three.js applies Euler XYZ angles negated,
      // which is the inverse of Ry(y) Rx(x), so these angles turn the environment with the camera.
      scene.environmentRotation.set(orbit.phi - initial.phi, orbit.theta - initial.theta, 0, 'XYZ');
      if (typeof scene.queueRender === 'function') {
        scene.queueRender();
      }
    }

    function start() {
      initial = viewer.getCameraOrbit();
      update();
    }

    viewer.addEventListener('camera-change', update);
    if (viewer.loaded) {
      start();
    } else {
      viewer.addEventListener('load', start, { once: true });
    }
  }

  function attachAll() {
    document.querySelectorAll('model-viewer[data-lighting-mode="viewer"]').forEach(attach);
  }

  if (!window.customElements) {
    return;
  }
  customElements.whenDefined('model-viewer').then(function () {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', attachAll);
    } else {
      attachAll();
    }
  });
})();
