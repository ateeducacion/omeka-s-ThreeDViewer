<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Media\Renderer\RendererInterface;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\MediaRepresentation;

class StlRenderer extends Abstract3DRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []): string
    {
        $config = $this->getViewerConfig($view);

        $view->headScript()->appendFile('https://cdn.jsdelivr.net/npm/three@0.120.1/build/three.min.js');
        $view->headScript()->appendFile('https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/loaders/STLLoader.js');
        $view->headScript()->appendFile(
            'https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/controls/OrbitControls.js'
        );
        $view->headScript()->appendFile($view->assetUrl('js/stl-viewer.js', 'ThreeDViewer'));

        $view->headStyle()->appendStyle('
            .media-render { position: relative; height: ' . $config['height'] . 'px; width: 100%; overflow: hidden; }
            #loading {
                position: absolute;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                color: white; background: rgba(0,0,0,0.7);
                padding: 15px; border-radius: 5px; z-index: 100;
            }
            .hidden { display: none !important; }
        ');

        $infoPanel = $this->renderInfoPanel($view, 'STL Viewer', $config['showGrid']);

        return $infoPanel
             . '<div id="loading" data-stl-url="' . $view->escapeHtmlAttr($media->originalUrl())
             . '" data-background-color="' . $view->escapeHtmlAttr($config['backgroundColor'])
             . '" data-auto-rotate="' . ($config['autoRotate'] ? 'true' : 'false')
             . '" data-show-grid="' . ($config['showGrid'] ? 'true' : 'false') . '">'
             . 'Loading STL model...</div>';
    }
}
