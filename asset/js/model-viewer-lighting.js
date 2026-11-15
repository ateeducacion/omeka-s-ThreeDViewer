/**
 * Lighting for <model-viewer>
 *
 * model-viewer lights a model with an environment map fixed in the scene while camera-controls
 * orbit the camera, so the lighting stays with the model. For data-lighting-mode="viewer" this
 * rotates the environment with the camera orbit, so the model turns under the lights instead.
 *
 * model-viewer has no lamps, so for a light rig (data-lights) this paints the rig into an
 * environment image (lighting-rig.js) and gives model-viewer that image instead of its own.
 */
(function () {
  'use strict';

  /**
   * Each viewer's rig environments: the lights painted into each Blob URL given to model-viewer and
   * not yet released, the environment shown ({ url, lights }), the URL it is loading, and the rig to
   * paint once that has loaded
   */
  const environments = new WeakMap();

  /**
   * How long to wait for an environment where model-viewer's cache cannot be reached, so that there
   * is no telling whether it has started loading or failed, before painting the next
   */
  const ENVIRONMENT_TIMEOUT = 10000;

  /** How often to look for model-viewer starting to load an environment */
  const ENVIRONMENT_POLL = 250;

  /**
   * The height of its up axis over which model-viewer's own environment lights the model from above:
   * -74 degrees. In the bundled model-viewer, the neutral room sits 3.5 below the model, so its side
   * panels' centres are 16-39 degrees up (the lowest, at [7, 8, -14], 16.04); tilted any way, every
   * panel is at or below level only once the up axis is 90 - 16.04 = 73.96 degrees below level.
   * Calibrated on the neutral room, and used the same for legacy and custom images.
   * Less 1e-6, so that -74 itself, rounded, still counts as above.
   */
  const ENVIRONMENT_ABOVE = Math.sin(-74 * Math.PI / 180) - 1e-6;

  /**
   * A switch for model-viewer's shadow under the model, which it draws whatever the lights: called
   * with false, it hides the shadow, and with true shows it again as configured. Null where this
   * model-viewer's scene has no shadow to reach.
   *
   * The shadow is hidden in the scene rather than by setting shadowIntensity to 0, at which
   * model-viewer shows any shadow baked into the model instead. model-viewer sets the shadow's
   * strength again as a model loads, as AR starts and ends, and as shadowIntensity changes, all
   * through the scene's setShadowIntensity(), so a hidden shadow is hidden again each time.
   *
   * Showing it again reads the scene's shadowIntensity and hiding it calls the shadow's
   * setIntensity(), so without both the switch would hide the shadow for good. model-viewer makes the
   * shadow only once a model shows one, so it is checked here only if made already.
   *
   * At strength 0 model-viewer makes no shadow and shows the shadows baked into the model instead,
   * through the scene's setBakedShadowVisibility(), which it calls again as it frames the model. While
   * hidden, that hides them too; where the scene has no such method, they are left as model-viewer has them.
   */
  function shadowSwitch(scene) {
    if (!scene || typeof scene.setShadowIntensity !== 'function' || typeof scene.shadowIntensity !== 'number'
      || (scene.shadow && typeof scene.shadow.setIntensity !== 'function')) {
      return null;
    }
    let shown = true;
    const setShadowIntensity = scene.setShadowIntensity;
    function hide() {
      if (!shown && scene.shadow && typeof scene.shadow.setIntensity === 'function') {
        scene.shadow.setIntensity(0);
      }
    }
    scene.setShadowIntensity = function () {
      setShadowIntensity.apply(this, arguments);
      hide();
    };
    const setBakedShadowVisibility = typeof scene.setBakedShadowVisibility === 'function'
      ? scene.setBakedShadowVisibility : null;
    if (setBakedShadowVisibility) {
      scene.setBakedShadowVisibility = function () {
        setBakedShadowVisibility.apply(this, shown ? arguments : [false]);
      };
    }
    return function (show) {
      if (show === shown) {
        return;
      }
      shown = show;
      if (show) {
        scene.setShadowIntensity(scene.shadowIntensity);
      } else {
        hide();
      }
      // The bundled model-viewer's setShadowIntensity() shows them again too, but not every version may
      if (setBakedShadowVisibility) {
        scene.setBakedShadowVisibility();
      }
      if (typeof scene.queueRender === 'function') {
        scene.queueRender();
      }
    };
  }

  /**
   * Whether the shadow should show for these lights, as lighting-rig.js's shadowShown() decides: with
   * no exposure, no light shines.
   *
   * @param {Function} [height] - As for shadowShown(): the y of a direction in the scene
   * @param {number} [above] - As for shadowShown()
   */
  function viewerShadowShown(viewer, lights, height, above) {
    return viewer.exposure <= 0 || window.ThreeDViewerLighting.shadowShown(lights, height, above);
  }

  /**
   * The lights of a rig, for the controller, their directions from the side the default view looks
   * from
   */
  function rigLights(rig, side) {
    const lighting = window.ThreeDViewerLighting;
    return [{ id: 'ambient', kind: 'ambient', color: rig.ambient.color, strength: rig.ambient.strength }]
      .concat(rig.lights.map(function (light, i) {
        return {
          id: 'light' + (i + 1),
          kind: 'light',
          color: light.color,
          strength: light.strength,
          direction: lighting.direction(light.azimuth, light.elevation, side, false)
        };
      }));
  }

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

  /**
   * How far model-viewer's auto-rotate has turned the model, in radians about the up axis. Unlike the
   * other viewers' auto-rotate, which moves the camera, model-viewer's turns the model on a turntable
   * (its pivot), with no camera-change event.
   */
  function turntable(viewer) {
    const yaw = viewer.turntableRotation;
    return typeof yaw === 'number' && isFinite(yaw) ? yaw : 0;
  }

  /**
   * The side of the model the default view looks from: the camera's, before anyone orbits it.
   */
  function viewSide(viewer) {
    const orbit = viewer.getCameraOrbit();
    return { x: Math.sin(orbit.theta), z: Math.cos(orbit.theta) };
  }

  /**
   * model-viewer's cache of environment images by URL (TextureUtils.skyboxCache), which it never
   * empties while the page is open, or null where this version of model-viewer keeps none there.
   * Like the scene, it is not exported: it is reached through the element's Symbol(renderer).
   */
  function environmentCache(viewer) {
    try {
      for (let o = viewer; o; o = Object.getPrototypeOf(o)) {
        const symbol = Object.getOwnPropertySymbols(o).find(function (s) {
          return s.description === 'renderer';
        });
        if (symbol) {
          const renderer = viewer[symbol];
          const cache = renderer && renderer.textureUtils && renderer.textureUtils.skyboxCache;
          return cache instanceof Map ? cache : null;
        }
      }
    } catch (e) {
      // Not this model-viewer's insides
    }
    return null;
  }

  /**
   * Free an environment that model-viewer no longer shows: its image, which model-viewer would
   * otherwise keep, then its Blob URL.
   */
  function release(viewer, url) {
    const cache = environmentCache(viewer);
    const key = url + '#.hdr';
    const loading = cache ? cache.get(key) : undefined;
    if (!loading) {
      URL.revokeObjectURL(url);
      return;
    }
    cache.delete(key);
    Promise.resolve(loading).then(function (texture) {
      if (texture) {
        texture.dispose();
      }
    }, function () {}).then(function () {
      URL.revokeObjectURL(url);
    });
  }

  /**
   * Light the model with an environment painted from the rig, its directions taken from the side
   * the default view looks from.
   *
   * One environment loads at a time: a rig painted meanwhile waits for it, and replaces any rig
   * already waiting. Each environment is released once the next is shown, and state.onShown() is
   * then called, if set.
   *
   * The lights painted, { strength, direction }, are kept with the environment's URL as they are at
   * painting, taken from the same angles as the image: the controller changes its lights in place.
   * Until model-viewer shows an environment, the first rig painted counts as shown.
   */
  function applyRig(viewer, rig, back) {
    const lighting = window.ThreeDViewerLighting;
    let state = environments.get(viewer);
    if (!state) {
      state = { painted: new Map(), shown: null, loading: null, timer: 0, waiting: null };
      environments.set(viewer, state);
      viewer.addEventListener('environment-change', function () {
        // The environment model-viewer shows is the one last given it, whichever it was loading.
        // ponytail: without its cache, an environment given up on that loads after the next is
        // credited with the next's lights; reading model-viewer's current map would tell them apart
        const url = String(viewer.environmentImage).replace(/#\.hdr$/, '');
        if (state.painted.has(url)) {
          loaded(viewer, state, url, true);
        }
      });
    }
    if (state.loading) {
      state.waiting = { rig: rig, back: back };
      return;
    }

    const hdr = lighting.rgbe(lighting.environment(rig, back));
    const url = URL.createObjectURL(new Blob([hdr], { type: 'image/vnd.radiance' }));
    const lights = rig.lights.map(function (light) {
      return { strength: light.strength, direction: lighting.direction(light.azimuth, light.elevation, back, false) };
    });
    state.painted.set(url, lights);
    if (!state.shown) {
      state.shown = { url: null, lights: lights };
    }
    state.loading = url;
    // model-viewer chooses its HDR loader by the URL's ending; fetch ignores the fragment
    viewer.environmentImage = url + '#.hdr';
    watch(viewer, state, url);
  }

  /**
   * Watch for the environment being loaded failing: model-viewer throws, with no event. It loads
   * an environment only once the element is on screen, which may be long after it is given one,
   * so wait for it to start, however long that takes, rather than give up on it and free an image
   * it has yet to load.
   */
  function watch(viewer, state, url) {
    if (state.loading !== url) {
      return;
    }
    const cache = environmentCache(viewer);
    if (!cache) {
      // Stop waiting for it, but keep its URL, which model-viewer may yet load
      state.timer = setTimeout(function () {
        if (state.loading === url) {
          state.loading = null;
          next(viewer, state);
        }
      }, ENVIRONMENT_TIMEOUT);
      return;
    }
    const loading = cache.get(url + '#.hdr');
    if (!loading) {
      state.timer = setTimeout(function () {
        watch(viewer, state, url);
      }, ENVIRONMENT_POLL);
      return;
    }
    // Loading: it is either shown (environment-change) or fails
    Promise.resolve(loading).then(null, function () {
      if (state.loading === url) {
        loaded(viewer, state, url, false);
      }
    });
  }

  /**
   * An environment is shown (or the one loading has failed, and model-viewer then shows none):
   * release the one it replaces (and the one that failed), then paint the rig waiting, if any.
   */
  function loaded(viewer, state, url, shown) {
    if (state.loading === url) {
      clearTimeout(state.timer);
      state.loading = null;
    }
    const old = state.shown.url;
    if (old !== url) {
      state.shown = { url: shown ? url : null, lights: shown ? state.painted.get(url) : [] };
      [old, shown ? null : url].forEach(function (freed) {
        if (freed) {
          state.painted.delete(freed);
          release(viewer, freed);
        }
      });
    }
    try {
      if (state.onShown) {
        state.onShown();
      }
    } finally {
      next(viewer, state);
    }
  }

  /** Paint the rig waiting, if any */
  function next(viewer, state) {
    const waiting = state.waiting;
    state.waiting = null;
    if (waiting) {
      applyRig(viewer, waiting.rig, waiting.back);
    }
  }

  /**
   * The lighting fixed to the model or to the viewer, and changed by the lighting controls where
   * the page shows them: see ThreeDViewerLighting.controller() in lighting-rig.js.
   *
   * Either way the environment is turned by the controller's frame. With a rig, each change to a
   * light repaints the environment. With model-viewer's own environment, its direction and height
   * turn the environment so that its overhead light comes from there.
   *
   * The controller works in the model's frame. Auto-rotate turns the model, not the camera, so the
   * camera's orientation is taken relative to the model (the model's turn counts as the camera
   * turning the other way), and the environment turns with the model on top of the frame: fixed to
   * the model, the lights turn with it; fixed to the viewer, the frame turns back as the model turns,
   * and the lights stay put. The turntable is watched each frame, as nothing announces its turns.
   *
   * The shadow is hidden while lights shine but none, turned by the frame, from above the model (see
   * viewerShadowShown()); model-viewer's own environment, only once its up axis is below
   * ENVIRONMENT_ABOVE. The model's turn is about the vertical, so it raises or lowers no light.
   * A rig's shadow follows the lighting on screen: the lights of the environment shown, turned by the
   * frame now, decided again as each repaint is shown and as the frame turns. Until a repaint is
   * shown, those are the rig's own lights, as attach() decided before the model loaded.
   *
   * @param {Object|null} rig - The rig as painted, its strengths already multiplied by brightness
   * @param {{x: number, z: number}} side - The side of the model the default view looks from
   * @param {?function(boolean)} shadow - From shadowSwitch()
   * @param {Object|null} scene - From getScene()
   */
  function setUpLighting(viewer, rig, side, shadow, scene) {
    const lighting = window.ThreeDViewerLighting;
    // model-viewer's three.js classes, which its bundle does not export, from the scene's own objects.
    // Scene.environmentRotation was added in three.js r162.
    const turnable = scene && scene.environmentRotation && scene.quaternion && scene.position;
    if (!turnable && viewer.dataset.lightingMode === 'viewer') {
      console.warn('ThreeDViewer: viewer-fixed lighting is not supported by this model-viewer version.');
    }
    const Quaternion = turnable ? scene.quaternion.constructor : null;
    const Vector3 = turnable ? scene.position.constructor : null;
    const Euler = turnable ? scene.environmentRotation.constructor : null;
    const degrees = Math.PI / 180;
    // The model's turn since the lighting was set up, which its default view is taken from
    const startYaw = turntable(viewer);
    function turned() {
      return turntable(viewer) - startYaw;
    }

    let lights;
    if (rig) {
      lights = rigLights(rig, side);
      // Each repaint loads a new environment; without this, the loading bar flashes as a slider moves
      viewer.style.setProperty('--progress-bar-height', '0px');
    } else {
      const environment = { id: 'environment', kind: 'environment', strength: viewer.exposure };
      if (turnable) {
        // Its overhead light, untouched
        environment.direction = { x: 0, y: 1, z: 0 };
      }
      lights = [environment];
    }

    // Over which a light is above the model: the rig's lights at level, the environment lower
    const above = rig ? undefined : ENVIRONMENT_ABOVE;

    let repaint = 0;
    let changed = false;
    // Where model-viewer's cache cannot be reached, each environment painted stays in memory, so
    // paint only once the controls pause rather than as often as a slider moves
    const pause = rig && !environmentCache(viewer) ? 250 : 0;
    let latest = null;
    let placed = null;   // The lights and frame as last placed, to turn again with the model
    let following = null;   // The controller's update for a camera move, if it wants one
    const state = rig ? environments.get(viewer) : null;
    function paint() {
      repaint = 0;
      applyRig(viewer, { ambient: latest[0], lights: latest.slice(1) }, side);
    }

    function height(d) {
      return heightTurned(placed.frame, d);
    }

    /** Show the shadow or not for the lighting on screen, turned by the frame */
    function decideShadow() {
      if (!shadow || !placed) {
        return;
      }
      shadow(viewerShadowShown(viewer, state ? state.shown.lights : placed.all, placed.frame ? height : null, above));
    }
    if (state) {
      state.onShown = decideShadow;
    }

    /** Turn the environment by the frame, and model-viewer's own by its direction too */
    function orient(all, frame) {
      if (!frame) {
        return;
      }
      const turn = frame.clone();
      const environment = all[0];
      if (!rig && environment.direction) {
        // Its azimuth is from the default view's side, which is theta round from +z
        turn.multiply(new Quaternion().setFromEuler(new Euler(
          (90 - environment.elevation) * degrees,
          environment.azimuth * degrees + Math.atan2(side.x, side.z),
          0,
          'YXZ'
        )));
      }
      // Then with the model, from the model's frame to the scene's
      const world = new Quaternion().setFromAxisAngle(new Vector3(0, 1, 0), turned()).multiply(turn);
      // three.js samples the environment through the negated Euler angles: R_XYZ(-e) = turn^-1
      const inverse = new Euler().setFromQuaternion(world.invert(), 'XYZ');
      scene.environmentRotation.set(-inverse.x, -inverse.y, -inverse.z, 'XYZ');
      if (typeof scene.queueRender === 'function') {
        scene.queueRender();
      }
    }

    /**
     * The camera's orientation relative to the model, from its orbit less the model's turn,
     * Ry(theta - turned) Rx(phi): it turns +y to the camera's side
     */
    function orientation() {
      const orbit = viewer.getCameraOrbit();
      return new Quaternion().setFromEuler(new Euler(orbit.phi, orbit.theta - turned(), 0, 'YXZ'));
    }

    let listener = null;
    lighting.controller(viewer, {
      lights: lights,
      back: side,
      leftHanded: false,
      mode: viewer.dataset.lightingMode,
      turns: turnable ? {
        identity: function () {
          return new Quaternion();
        },
        clone: function (turn) {
          return turn.clone();
        },
        then: function (first, second) {
          return second.clone().multiply(first);
        },
        invert: function (turn) {
          return turn.clone().invert();
        },
        apply: function (turn, v) {
          const p = new Vector3(v.x, v.y, v.z).applyQuaternion(turn);
          return { x: p.x, y: p.y, z: p.z };
        },
        camera: orientation,
        since: function (start) {
          // The camera's turn since then, from wherever round the model it was
          return orientation().multiply(start.clone().invert());
        }
      } : null,
      follow: function (update) {
        if (listener) {
          viewer.removeEventListener('camera-change', listener);
        }
        listener = update;
        following = update;
        if (listener) {
          viewer.addEventListener('camera-change', listener);
        }
      },
      set: function (light) {
        if (rig) {
          changed = true;
        } else {
          viewer.exposure = light.strength;
        }
      },
      place: function (all, frame) {
        latest = all;
        placed = { all: all, frame: frame };
        if (changed && pause) {
          clearTimeout(repaint);
          repaint = setTimeout(paint, pause);
        } else if (changed && !repaint) {
          // Painting takes a few tens of milliseconds: at most once a frame while a slider moves
          repaint = requestAnimationFrame(paint);
        }
        changed = false;
        orient(all, frame);
        decideShadow();
      }
    });

    // Only auto-rotate turns the turntable, and the module sets it once, in the markup
    if (turnable && viewer.autoRotate) {
      let last = turntable(viewer);
      const watchTurntable = function () {
        const now = turntable(viewer);
        if (now !== last) {
          last = now;
          // Fixed to the viewer, the frame turns back; with the panel open, the directions shown turn
          if (following) {
            following();
          }
          if (placed) {
            orient(placed.all, placed.frame);
          }
        }
        requestAnimationFrame(watchTurntable);
      };
      requestAnimationFrame(watchTurntable);
    }
  }

  /**
   * The y of a direction turned by a quaternion, without making a vector: place() runs every frame
   * while auto-rotate turns lights fixed to the viewer
   */
  function heightTurned(q, d) {
    // v + 2w (q × v) + 2 q × (q × v), for the unit quaternion q
    const cx = q.y * d.z - q.z * d.y;
    const cy = q.z * d.x - q.x * d.z;
    const cz = q.x * d.y - q.y * d.x;
    return d.y + 2 * (q.w * cy + q.z * cx - q.x * cz);
  }

  function attach(viewer) {
    if (viewer.dataset.lightingAttached) {
      return;
    }
    viewer.dataset.lightingAttached = 'true';
    const lighting = window.ThreeDViewerLighting;
    const controls = lighting && lighting.hasControls(viewer);

    // Set before the model loads, so the first frame already has the rig's lighting
    let rig = lighting ? lighting.parse(viewer) : null;
    if (rig && controls) {
      // The controls show each light's strength with brightness included, as in the other viewers
      const brightness = viewer.exposure;
      rig.ambient.strength *= brightness;
      rig.lights.forEach(function (light) {
        light.strength *= brightness;
      });
      viewer.exposure = 1;
    }
    const side = viewSide(viewer);
    if (rig) {
      applyRig(viewer, rig, side);
    }

    // A rig also needs turning with the model, as auto-rotate turns it
    if (lighting && (controls || rig || viewer.dataset.lightingMode === 'viewer')) {
      // Decided for the default view before the model loads, so no frame shows a shadow to be hidden
      const scene = getScene(viewer);
      const shadow = shadowSwitch(scene);
      if (shadow && rig) {
        shadow(viewerShadowShown(viewer, environments.get(viewer).shown.lights));
      }
      if (viewer.loaded) {
        setUpLighting(viewer, rig, side, shadow, scene);
      } else {
        viewer.addEventListener('load', function () {
          setUpLighting(viewer, rig, side, shadow, scene);
        }, { once: true });
      }
    }
  }

  function attachAll() {
    document.querySelectorAll(
      'model-viewer[data-lighting-mode="viewer"], model-viewer[data-lights], model-viewer[data-lighting-controls]'
    ).forEach(attach);
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
