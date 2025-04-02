<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Media\Renderer\RendererInterface;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\MediaRepresentation;

class GlbRenderer extends Abstract3DRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []): string
    {
        $config = $this->getViewerConfig($view);

        $view->headScript()->appendFile(
            'https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js',
            'module'
        );

        $infoPanel = $this->renderInfoPanel($view, 'GLB Viewer', $config['showGrid']);

        return '<div style="position: relative; width: 100%; height: ' . $config['height'] . 'px;">'
             . $infoPanel
             . '<model-viewer src="' . $media->originalUrl() . '" '
             . 'alt="' . htmlspecialchars($media->displayTitle()) . '" camera-controls '
             . ($config['autoRotate'] ? 'auto-rotate ' : '')
             . 'style="width: 100%; height: 100%; background-color: ' . $config['backgroundColor'] . ';">'
             . '</model-viewer>'
             . '</div>';
    }
}
