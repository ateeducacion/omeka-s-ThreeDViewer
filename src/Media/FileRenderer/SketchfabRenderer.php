<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Media\FileRenderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\MediaRepresentation;

class SketchfabRenderer extends Abstract3DRenderer implements RendererInterface
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []): string
    {
        $config = $this->getViewerConfig($view);
        $sketchfabUid = $media->value('three_d_viewer_sketchfab_uid', ['default' => '']);

        if (empty($sketchfabUid)) {
            return '<p>No Sketchfab UID provided.</p>';
        }

        $view->headScript()->appendFile('https://static.sketchfab.com/api/sketchfab-viewer-1.12.1.js');

        $iframeId = 'sketchfab-iframe-' . uniqid();

        $script = "
            var iframe = document.getElementById('{$iframeId}');
            var client = new Sketchfab(iframe);
            client.init('{$sketchfabUid}', {
                success: function(api) {
                    api.start();
                    api.addEventListener('viewerready', function() {
                        console.log('Viewer is ready');
                    });
                },
                error: function() {
                    console.error('Viewer error');
                }
            });
        ";

        $view->headScript()->appendScript($script);

        return '<div style="position: relative; width: 100%; height: ' . $config['height'] . 'px;">'
            . '<iframe title="' . htmlspecialchars($media->displayTitle()) . '" id="' . $iframeId . '" allow="autoplay; fullscreen; xr-spatial-tracking" allowfullscreen style="width: 100%; height: 100%; border: 0;"></iframe>'
            . '</div>';
    }
}