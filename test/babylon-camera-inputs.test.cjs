// Run with: node --test test/babylon-camera-inputs.test.cjs
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

const source = readFileSync(join(__dirname, '../asset/js/babylon-viewer.js'), 'utf8');

function initialise(cameraType) {
    let camera;
    class UniversalCamera {
        constructor() {
            camera = this;
            this.wheelAdditions = 0;
            this.inputs = {
                attached: {},
                addMouseWheel: () => {
                    this.wheelAdditions++;
                    this.inputs.attached.mousewheel = {};
                }
            };
        }
        setTarget() {}
        attachControl(canvas, noPreventDefault) {
            this.controlCanvas = canvas;
            this.noPreventDefault = noPreventDefault;
        }
    }
    class ArcRotateCamera extends UniversalCamera {
        constructor() {
            super();
            this.inputs.attached.mousewheel = {};
        }
    }
    const BABYLON = {
        UniversalCamera,
        ArcRotateCamera,
        Vector3: class { static Zero() { return {}; } },
        Engine: class { runRenderLoop() {} },
        Scene: class { constructor() { this.lights = []; } },
        Color3: { FromHexString: () => ({ r: 0, g: 0, b: 0 }) },
        Color4: class {},
        HemisphericLight: class {},
        SceneLoader: { Append() {} }
    };
    const canvas = { dataset: { camera: cameraType, modelUrl: '/model.glb' } };
    runInNewContext(source, {
        BABYLON,
        window: { BABYLON, addEventListener() {} },
        document: { readyState: 'complete', querySelectorAll: () => [canvas] }
    });
    return { camera, canvas };
}

for (const type of ['arcRotate', 'universal', 'firstPerson']) {
    test(`${type}: camera controls may prevent browser scrolling`, () => {
        const { camera, canvas } = initialise(type);
        assert.equal(camera.controlCanvas, canvas);
        assert.equal(camera.noPreventDefault, false);
    });

    test(`${type}: adds a wheel input only when missing`, () => {
        const { camera } = initialise(type);
        assert.equal(camera.wheelAdditions, type === 'arcRotate' ? 0 : 1);
        assert.ok(camera.inputs.attached.mousewheel);
    });
}
