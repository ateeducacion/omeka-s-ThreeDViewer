<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Media\FileRenderer\RendererInterface;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\MediaRepresentation;

class GlbRenderer extends Abstract3DRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []): string
    {
        $config = $this->getViewerConfig($view);

        $view->headLink()->appendStylesheet($view->assetUrl('css/model-viewer-controls.css', 'ThreeDViewer'));
        $view->headScript()->appendFile(
            $view->assetUrl('vendor/model-viewer/model-viewer.min.js', 'ThreeDViewer'),
            'module'
        );
        $view->headScript()->appendFile($view->assetUrl('js/viewer-controls.js', 'ThreeDViewer'));
        if ($config['lightingMode'] === 'viewer') {
            $view->headScript()->appendFile($view->assetUrl('js/model-viewer-lighting.js', 'ThreeDViewer'));
        }

        $rawUrl = $media->originalUrl();
        $protocolRelativeUrl = preg_replace('/^https?:/', '', $rawUrl);
        $background = $view->escapeHtmlAttr($config['backgroundColor']);
        $autoRotate = $config['autoRotate'] ? 'auto-rotate ' : '';
        $modelViewerSrc = $view->escapeHtmlAttr($protocolRelativeUrl);
        $altText = $view->escapeHtmlAttr($media->displayTitle());
        $enterFullscreen = $view->escapeHtmlAttr($view->translate('View 3D model fullscreen'));
        $exitFullscreen = $view->escapeHtmlAttr($view->translate('Exit fullscreen 3D model'));
        $helpLabel = $view->escapeHtmlAttr($view->translate('3D viewer controls'));
        $helpText = $view->escapeHtml($view->translate('Use mouse to rotate, zoom and pan'));
        $gridOverlay = $config['showGrid'] ? '<div class="grid-overlay"></div>' : '';

        return '<div class="threedviewer-model-stage" style="position: relative; width: 100%; height: '
             . (int) $config['height'] . 'px;">'
             . $gridOverlay
             . '<model-viewer src="' . $modelViewerSrc . '" '
             . 'alt="' . $altText . '" camera-controls '
             . 'data-lighting-mode="' . $config['lightingMode'] . '" '
             . $autoRotate
             . 'style="width: 100%; height: 100%; background-color: ' . $background . ';">'
             . '</model-viewer>'
             . '<div class="threedviewer-control threedviewer-fullscreen">'
             . '<button type="button" aria-label="' . $enterFullscreen . '" '
             . 'title="' . $enterFullscreen . '" data-enter-label="' . $enterFullscreen . '" '
             . 'data-exit-label="' . $exitFullscreen . '">⛶</button></div>'
             . '<details class="threedviewer-control threedviewer-help">'
             . '<summary aria-label="' . $helpLabel . '">?</summary><p>' . $helpText . '</p></details>'
             . '</div>';
    }
}
