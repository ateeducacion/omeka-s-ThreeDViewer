<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Media\Renderer\RendererInterface;

abstract class Abstract3DRenderer implements RendererInterface
{
    /**
     * Get configuration from global and site settings.
     */
    protected function getViewerConfig(PhpRenderer $view): array
    {
        $default = [
            'height' => 500,
            'autoRotate' => true,
            'backgroundColor' => '#ffffff',
            'showGrid' => false,
        ];

        try {
            $setting = $view->plugin('setting');
            $default['height'] = $setting('threedviewer_viewer_height', $default['height']);
            $default['autoRotate'] = $setting('threedviewer_auto_rotate', $default['autoRotate']);
            $default['backgroundColor'] = $setting('threedviewer_background_color', $default['backgroundColor']);
            $default['showGrid'] = $setting('threedviewer_show_grid', $default['showGrid']);

            if ($view->params()->fromRoute('__SITE__')) {
                $siteSetting = $view->plugin('siteSetting');
                $default['height'] = $siteSetting('threedviewer_site_viewer_height', $default['height']);
                $default['autoRotate'] = $siteSetting('threedviewer_site_auto_rotate', $default['autoRotate']);
                $default['backgroundColor'] = $siteSetting(
                    'threedviewer_site_background_color',
                    $default['backgroundColor']
                );
                $default['showGrid'] = $siteSetting('threedviewer_site_show_grid', $default['showGrid']);
            }
        } catch (\Throwable $e) {
            error_log('Error getting settings: ' . $e->getMessage());
        }

        return $default;
    }

    /**
     * Render shared info panel and optional grid style.
     */
    protected function renderInfoPanel(PhpRenderer $view, string $title, bool $showGrid): string
    {
        $view->headStyle()->appendStyle('
            .model-info, #info {
                position: absolute;
                top: 10px;
                width: 100%;
                text-align: center;
                color: white;
                z-index: 100;
                pointer-events: none;
            }
        ');

        if ($showGrid) {
            $view->headStyle()->appendStyle('
                .grid-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-image: 
                        linear-gradient(to right, rgba(0, 255, 0, 0.2) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(0, 255, 0, 0.2) 1px, transparent 1px);
                    background-size: 20px 20px;
                    pointer-events: none;
                    z-index: 1;
                }
            ');
            return '<div class="model-info">' . $view->translate($title)
                 . ' - ' . $view->translate('Use mouse to rotate, zoom and pan') . '</div>'
                 . '<div class="grid-overlay"></div>';
        }

        return '<div class="model-info">' . $view->translate($title)
             . ' - ' . $view->translate('Use mouse to rotate, zoom and pan') . '</div>';
    }
}
