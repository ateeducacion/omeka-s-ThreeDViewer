<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;

class StlRenderer implements RendererInterface
{
    /**
     * Render an STL 3D model using three.js
     *
     * @param PhpRenderer $view
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        // Get settings - use default values if we're in admin context
        $height = 500;
        $autoRotate = true;
        $backgroundColor = '#ffffff';
        $showGrid = false;
        
        try {
            $setting = $view->plugin('setting');
            $height = $setting('threedviewer_viewer_height', 500);
            $autoRotate = $setting('threedviewer_auto_rotate', true);
            $backgroundColor = $setting('threedviewer_background_color', '#ffffff');
            $showGrid = $setting('threedviewer_show_grid', false);
            
            // Only try to get site settings if we're in a site context
            if ($view->params()->fromRoute('__SITE__')) {
                $siteSetting = $view->plugin('siteSetting');
                $siteHeight = $siteSetting('threedviewer_site_viewer_height', null);
                $siteAutoRotate = $siteSetting('threedviewer_site_auto_rotate', null);
                $siteBackgroundColor = $siteSetting('threedviewer_site_background_color', null);
                $siteShowGrid = $siteSetting('threedviewer_site_show_grid', null);
                
                // Override with site-specific settings if available
                if ($siteHeight !== null) {
                    $height = $siteHeight;
                }
                
                if ($siteAutoRotate !== null) {
                    $autoRotate = $siteAutoRotate;
                }
                
                if ($siteBackgroundColor !== null) {
                    $backgroundColor = $siteBackgroundColor;
                }
                
                if ($siteShowGrid !== null) {
                    $showGrid = $siteShowGrid;
                }
            }
        } catch (\Exception $e) {
            // If there's any error with settings, use defaults
            error_log('Error getting settings: ' . $e->getMessage());
        }
        
        // Add Three.js core library from CDN (versión estable)
        $view->headScript()->appendFile(
            'https://cdn.jsdelivr.net/npm/three@0.120.1/build/three.min.js'
        );
        
        // Add STLLoader from CDN
        $view->headScript()->appendFile(
            'https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/loaders/STLLoader.js'
        );
        
        // Add OrbitControls from CDN
        $view->headScript()->appendFile(
            'https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/controls/OrbitControls.js'
        );
        
        // Add inline script for STL viewer
        $view->headScript()->appendFile($view->assetUrl('js/stl-viewer.js', 'ThreeDViewer'));
                

// En tu renderer PHP, añade:
        $view->headStyle()->appendStyle('
    .media-render { 
        position: relative; 
        height: ' . $height . 'px;
        width: 100%;
        overflow: hidden;
    }
    #info {
        position: absolute;
        top: 10px;
        width: 100%;
        text-align: center;
        color: white;
        z-index: 100;
        pointer-events: none;
    }
    #loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        background: rgba(0,0,0,0.7);
        padding: 15px;
        border-radius: 5px;
        z-index: 100;
    }
    .hidden {
        display: none !important;
    }
');


        // Log which scripts are being loaded
        error_log("Loading Three.js scripts for STL rendering");
        
        // Generate a unique ID for the container
        $containerId = 'stl-viewer-' . $media->id();
        
        // En tu método render del StlRenderer.php
        // Create the viewer container with data attributes for configuration
        $html = '<div id="info">STL Viewer - Use mouse to rotate, zoom and pan</div>';
        $html .= '<div id="loading" data-stl-url="'
                 . $view->escapeHtmlAttr($media->originalUrl())
                 . '" data-background-color="' . $view->escapeHtmlAttr($backgroundColor)
                 . '" data-auto-rotate="' . ($autoRotate ? 'true' : 'false')
                 . '" data-show-grid="' . ($showGrid ? 'true' : 'false')
                 . '">'
                 . 'Loading STL model...</div>';

        // Add the script to initialize the STL viewer
        error_log("Initializing STL viewer for file: " . $media->originalUrl());
        // $view->inlineScript()->appendScript(
        //     "
        //     // Esperar a que la página esté completamente cargada
        //     window.addEventListener('load', function() {
        //         // Comprobar si Three.js está cargado
        //         if (typeof THREE === 'undefined') {
        //             console.error('THREE no está definido. Asegúrate de que Three.js está cargado correctamente.');
        //             document.getElementById('{$containerId}').innerHTML = '<p>Error: No se pudo cargar Three.js</p>';
        //             return;
        //         }
                
        //         // Inicializar el visor
        //         try {
        //             console.log('Inicializando visor STL para: {$media->originalUrl()}');
        //             window.initStlViewer('{$containerId}', '{$media->originalUrl()}', {
        //                 autoRotate: " . ($autoRotate ? 'true' : 'false') . ",
        //                 height: " . $height . ",
        //                 backgroundColor: 0x121212,
        //                 modelColor: 0x999999
        //             });
        //         } catch (e) {
        //             console.error('Error al inicializar el visor STL:', e);
        //             document.getElementById('{$containerId}').innerHTML = '<p>Error: ' + e.message + '</p>';
        //         }
        //     });
        //     "
        // );
        
        return $html;
    }
}
