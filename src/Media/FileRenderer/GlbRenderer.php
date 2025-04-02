<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;

class GlbRenderer implements RendererInterface
{
    /**
     * Render a GLB/GLTF 3D model using the configured library
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        // Default settings
        $library = 'model-viewer';
        $height = 500;
        $autoRotate = true;
        $backgroundColor = '#ffffff';
        $showGrid = false;
        
        try {
            $setting = $view->plugin('setting');
            $defaultHeight = $setting('threedviewer_viewer_height', 500);
            $defaultAutoRotate = $setting('threedviewer_auto_rotate', true);
            $defaultBackgroundColor = $setting('threedviewer_background_color', '#ffffff');
            $defaultShowGrid = $setting('threedviewer_show_grid', false);
            
            // Use global settings as defaults
            $height = $defaultHeight;
            $autoRotate = $defaultAutoRotate;
            $backgroundColor = $defaultBackgroundColor;
            $showGrid = $defaultShowGrid;
        } catch (\Exception $e) {
            // If there's any error with settings, use defaults
            error_log('Error getting settings: ' . $e->getMessage());
        }
        
        return $this->renderWithModelViewer($view, $media, [
            'height' => $height,
            'autoRotate' => $autoRotate,
            'backgroundColor' => $backgroundColor,
            'showGrid' => $showGrid,
        ]);
    }
    
    /**
     * Render using model-viewer.js
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    protected function renderWithModelViewer(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        // Load model-viewer from CDN
        $view->headScript()->appendFile(
            'https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js',
            'module'
        );
        
        // Set default options
        $height = isset($options['height']) ? $options['height'] : 500;
        $autoRotate = isset($options['autoRotate']) ? $options['autoRotate'] : true;
        $backgroundColor = isset($options['backgroundColor']) ? $options['backgroundColor'] : '#ffffff';
        $showGrid = isset($options['showGrid']) ? $options['showGrid'] : false;
        
        // Add info text
        $view->headStyle()->appendStyle('
            .model-info {
                position: absolute;
                top: 10px;
                width: 100%;
                text-align: center;
                color: white;
                z-index: 100;
                pointer-events: none;
            }
        ');
        
        // Create the model-viewer element
        $html = '<div style="position: relative; width: 100%; height: ' . $height . 'px;">';
        $html .= '<div class="model-info">';
        $html .= $view->translate('GLB Viewer')
        $html .= ' - ';
        $html .= $view->translate('Use mouse to rotate, zoom and pan') . '</div>';
        
        // Add grid if enabled
        if ($showGrid) {
            // Add CSS for grid
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
            
            $html .= '<div class="grid-overlay"></div>';
        }
        
        $html .= '<model-viewer src="' . $media->originalUrl() . '" ';
        $html .= 'alt="' . htmlspecialchars($media->displayTitle()) . '" ';
        $html .= 'camera-controls ';
        
        // Only add auto-rotate attribute if it's enabled in settings
        if ($autoRotate) {
            $html .= 'auto-rotate ';
        }
        
        $html .= 'style="width: 100%; height: 100%; background-color: ' . $backgroundColor . ';"></model-viewer>';
        $html .= '</div>';
        
        return $html;
    }
}
