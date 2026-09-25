<?php

declare(strict_types=1);

namespace Viewer3DTest\Renderer;

use PHPUnit\Framework\TestCase;
use Viewer3DTest\Doubles\DummyPhpRenderer;
use ThreeDViewer\Media\FileRenderer\Viewer3DRenderer;
use Omeka\Api\Representation\MediaRepresentation;

class Viewer3DRendererTest extends TestCase
{
    private DummyPhpRenderer $view;
    private Viewer3DRenderer $renderer;

    protected function setUp(): void
    {
        $this->view = new DummyPhpRenderer();
        $this->renderer = new Viewer3DRenderer();
    }

    public function testRendersGlbWithModelViewer(): void
    {
        $media = new MediaRepresentation(
            'https://example.org/files/original/model.glb',
            'GLB Sample',
            'model.glb'
        );

        $html = $this->renderer->render($this->view, $media, []);

        $this->assertIsString($html);
        $this->assertStringContainsString('<model-viewer', $html, 'Should use model-viewer for GLB');
        $this->assertStringContainsString('GLB Viewer', $html, 'GLB info panel present');
    }

    public function testRendersStlWithCustomViewer(): void
    {
        $media = new MediaRepresentation(
            'https://example.org/files/original/mesh.stl',
            'STL Sample',
            'mesh.stl'
        );

        $html = $this->renderer->render($this->view, $media, []);

        $this->assertIsString($html);
        $this->assertStringContainsString('data-stl-url="//example.org/files/original/mesh.stl"', $html);
        $this->assertStringContainsString('STL Viewer', $html, 'STL info panel present');
    }

    public function testRendersWithBabylonWhenConfigured(): void
    {
        $this->view->setSettings([
            'threedviewer_default_library' => 'babylon',
        ]);

        $media = new MediaRepresentation(
            'https://example.org/files/original/model.gltf',
            'GLTF Sample',
            'model.gltf'
        );

        $html = $this->renderer->render($this->view, $media, []);

        $this->assertIsString($html);
        $this->assertStringContainsString('class="threedviewer-babylon-canvas"', $html);
        $this->assertStringContainsString('Babylon.js Viewer', $html, 'Babylon info panel present');
    }

    /**
     * @dataProvider lightingModeCases
     */
    public function testEveryViewerReceivesTheLightingMode(?string $setting, string $expected): void
    {
        $viewers = ['model.glb' => 'model-viewer', 'mesh.stl' => 'model-viewer', 'model.gltf' => 'babylon'];
        foreach ($viewers as $file => $library) {
            $view = new DummyPhpRenderer();
            $view->setSettings(array_filter([
                'threedviewer_default_library' => $library,
                'threedviewer_lighting_mode' => $setting,
            ]));
            $media = new MediaRepresentation('https://example.org/files/original/' . $file, 'Sample', $file);

            $html = $this->renderer->render($view, $media, []);

            $this->assertStringContainsString('data-lighting-mode="' . $expected . '"', $html, $file);
        }
    }

    public function lightingModeCases(): array
    {
        return [
            'default keeps lights fixed to the model' => [null, 'model'],
            'viewer' => ['viewer', 'viewer'],
            'unknown value falls back to model' => ['bogus', 'model'],
        ];
    }

    public function testModelViewerLightingScriptLoadsOnlyForViewerMode(): void
    {
        $media = new MediaRepresentation('https://example.org/files/original/model.glb', 'GLB', 'model.glb');
        $script = '/modules/ThreeDViewer/js/model-viewer-lighting.js';

        $this->renderer->render($this->view, $media, []);
        $this->assertNotContains($script, $this->view->headScript()->files);

        $view = new DummyPhpRenderer();
        $view->setSettings(['threedviewer_lighting_mode' => 'viewer']);
        $this->renderer->render($view, $media, []);
        $this->assertContains($script, $view->headScript()->files);
    }
}
