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

        $view->headScript()->appendFile(
            $view->assetUrl('vendor/model-viewer/model-viewer.min.js', 'ThreeDViewer'),
            'module'
        );
        $view->headScript()->appendFile($view->assetUrl('js/viewer-controls.js', 'ThreeDViewer'));
        if ($config['lightingMode'] === 'viewer') {
            $view->headScript()->appendFile($view->assetUrl('js/model-viewer-lighting.js', 'ThreeDViewer'));
        }

        $infoPanel = $this->renderInfoPanel(
            $view,
            'GLB Viewer', // @translate
            $config['showGrid']
        );

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

        $view->headStyle()->appendStyle('
            .threedviewer-model-stage .model-info { display: none; }
            .threedviewer-model-stage:fullscreen { width: 100%; height: 100vh !important; }
            .threedviewer-model-stage:fullscreen model-viewer { width: 100%; height: 100%; }
            .threedviewer-control { position: absolute; bottom: 12px; z-index: 101; }
            .threedviewer-fullscreen { left: 12px; cursor: pointer; }
            .threedviewer-help { right: 12px; }
            .threedviewer-control button, .threedviewer-help summary {
                display: inline-flex; align-items: center; justify-content: center;
                min-width: 36px; min-height: 36px; border: 1px solid #555;
                border-radius: 4px; background: #fff; color: #111; cursor: pointer;
            }
            .threedviewer-help summary { list-style: none; }
            .threedviewer-help summary::-webkit-details-marker { display: none; }
            .threedviewer-help p {
                position: absolute; right: 0; bottom: 40px; width: 220px;
                margin: 0; padding: 10px; border-radius: 4px;
                background: #fff; color: #111; box-shadow: 0 2px 10px #0006;
            }
        ');

        return '<div class="threedviewer-model-stage" style="position: relative; width: 100%; height: '
             . (int) $config['height'] . 'px;">'
             . $infoPanel
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
