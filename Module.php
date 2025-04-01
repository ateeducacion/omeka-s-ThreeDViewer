<?php
declare(strict_types=1);

namespace ThreeDViewer;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use ThreeDViewer\Form\ConfigForm;

/**
 * Main class for the 3DViewer module.
 */
class Module extends AbstractModule
{
    const VERSION = '1.0.0';

    /**
     * Retrieve the configuration array.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Execute logic when the module is installed.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $messenger = new Messenger();
        $message = new Message("3DViewer module installed.");
        $messenger->addSuccess($message);

        // Register 3D file types
        $this->updateWhitelist($serviceLocator);
    }
    
    /**
     * Register 3D file types in Omeka settings
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    protected function updateWhitelist(ServiceLocatorInterface $serviceLocator): void
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        
        // Get current media types
        $mediaTypes = $settings->get('media_types', []);
        
        // Add 3D model MIME types
        $newMimeTypes = [
            'model/stl',
            'model/gltf+json',
            'model/gltf-binary'
        ];
        
        foreach ($newMimeTypes as $mimeType) {
            if (!in_array($mimeType, $mediaTypes)) {
                $mediaTypes[] = $mimeType;
            }
        }
        
        $settings->set('media_types', $mediaTypes);
        
        // Get current file extensions
        $extensions = $settings->get('file_extensions', []);
        
        // Add 3D model extensions
        $newExtensions = ['stl', 'glb', 'gltf'];
        
        foreach ($newExtensions as $extension) {
            if (!in_array($extension, $extensions)) {
                $extensions[] = $extension;
            }
        }
        
        $settings->set('file_extensions', $extensions);
    }

    /**
     * Execute logic when the module is uninstalled.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $messenger = new Messenger();
        $message = new Message("3DViewer module uninstalled.");
        $messenger->addWarning($message);
    }
    
    /**
     * Register the file validator service and renderers.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     */
    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // Replace the default file validator with our custom one
        $sharedEventManager->attach(
            'Omeka\File\Validator',
            'validate.create',
            function ($event) {
                $validator = $event->getTarget();
                $tempFile = $event->getParam('tempFile');
                
                $fileInfo = $tempFile->getFileInfo();
                $filename = $fileInfo['name'];
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                error_log("File validation for: " . $filename . " with extension: " . $extension);
                
                // We don't need to change the MIME type, we'll detect by extension and source filename
                // Just log the file for debugging
                if ($extension === 'stl') {
                    error_log("Detected STL file during validation: " . $filename);
                } elseif ($extension === 'glb') {
                    error_log("Detected GLB file during validation: " . $filename);
                } elseif ($extension === 'gltf') {
                    error_log("Detected GLTF file during validation: " . $filename);
                }
            }
        );
        
        // Add a listener for the renderer.pre_render event
        $sharedEventManager->attach(
            'Omeka\Media\Renderer\Manager',
            'renderer.pre_render',
            function ($event) {
                $renderer = $event->getTarget();
                $media = $event->getParam('media');
                
                if (!$media) {
                    return;
                }
                
                $filename = $media->filename();
                $source = $media->source();
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                
                // Check if this is a 3D model file by extension or source
                if ($extension === 'stl' ||
                    stripos($filename, '.stl') !== false ||
                    ($source && stripos($source, '.stl') !== false)) {
                    error_log("Detected STL file in pre-render: " . $filename);
                    // We'll handle this in the handleMediaRender method
                } elseif ($extension === 'glb' ||
                          $extension === 'gltf' ||
                          stripos($filename, '.glb') !== false ||
                          stripos($filename, '.gltf') !== false ||
                          ($source && (stripos($source, '.glb') !== false || stripos($source, '.gltf') !== false))) {
                    error_log("Detected GLB/GLTF file in pre-render: " . $filename);
                    // We'll handle this in the handleMediaRender method
                }
            }
        );
        
        // Register our renderers for media rendering
        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            'rep.media.render',
            [$this, 'handleMediaRender']
        );
        
        // Also attach to the view.show.after event for items
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'addScriptsToPage']
        );
        
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'addScriptsToPage']
        );
        
        // We don't need to attach events for configuration forms
        // since we now use the getConfigForm and handleConfigForm methods
    }
    
    /**
     * Get the configuration form for this module.
     *
     * @param PhpRenderer $renderer
     * @return string
     */
    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        
        // Add message about file extensions and MIME types
        $messenger = new Messenger();
        $messenger->addWarning($renderer->translate(
            'IMPORTANT: To use 3D models, you need to add the following to your Omeka settings:
            • File extensions: stl, glb, gltf
            • MIME types: model/stl, model/gltf+json, model/gltf-binary
            
            Go to Settings > Security > File validation to add these.'
        ));
        
        $form = new ConfigForm;
        $form->init();
        
        $form->setData([
            'threedviewer_viewer_height' => $settings->get('threedviewer_viewer_height', 500),

            // 'threedviewer_viewer_height' => $settings->get('threedviewer_viewer_height', 500),
            
            'threedviewer_auto_rotate' => $settings->get('threedviewer_auto_rotate', true) ? '1' : '0',
            'threedviewer_show_grid' => $settings->get('threedviewer_show_grid', false) ? '1' : '0',


            // 'threedviewer_auto_rotate' => $settings->get('threedviewer_auto_rotate', true),
            'threedviewer_background_color' => $settings->get('threedviewer_background_color', '#ffffff'),
            // 'threedviewer_show_grid' => $settings->get('threedviewer_show_grid', false),
        ]);
        
        return $renderer->formCollection($form, false);
    }
    
    /**
     * Handle the configuration form submission.
     *
     * @param AbstractController $controller
     */
    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        
        $config = $controller->params()->fromPost();
        
        $settings->set('threedviewer_viewer_height', $config['threedviewer_viewer_height']);
        // $settings->set('threedviewer_auto_rotate', isset($config['threedviewer_auto_rotate']));
        $settings->set('threedviewer_background_color', $config['threedviewer_background_color']);
        // $settings->set('threedviewer_show_grid', isset($config['threedviewer_show_grid']));

        $settings->set('threedviewer_auto_rotate', $config['threedviewer_auto_rotate'] === '1');
        $settings->set('threedviewer_show_grid', $config['threedviewer_show_grid'] === '1');
    }
    
    /**
     * Add necessary scripts to the page for 3D rendering
     *
     * @param \Laminas\EventManager\Event $event
     */
    public function addScriptsToPage($event)
    {
        $view = $event->getTarget();
        $item = $view->item;
        if (!$item) {
            return;
        }
        
        // Check if this item has 3D media
        $has3DMedia = false;
        foreach ($item->media() as $media) {
            $mediaType = $media->mediaType();
            $extension = strtolower(pathinfo($media->filename(), PATHINFO_EXTENSION));
            
            // Check by extension first
            if (in_array($extension, ['stl', 'glb', 'gltf'])) {
                $has3DMedia = true;
                break;
            }
            
            // Then check by MIME type
            if (in_array($mediaType, ['model/stl', 'model/gltf-binary', 'model/gltf+json'])) {
                $has3DMedia = true;
                break;
            }
            
            // For STL files, also check for application/octet-stream and text/plain
            if ($extension === 'stl' && in_array($mediaType, ['application/octet-stream', 'text/plain'])) {
                $has3DMedia = true;
                break;
            }
        }
        
        if ($has3DMedia) {
            // Add all necessary scripts for both libraries
            $view->headScript()->appendFile(
                $view->assetUrl('js/three.min.js', 'ThreeDViewer')
            );
            $view->headScript()->appendFile(
                $view->assetUrl('js/GLTFLoader.js', 'ThreeDViewer')
            );
            $view->headScript()->appendFile(
                $view->assetUrl('js/STLLoader.js', 'ThreeDViewer')
            );
            $view->headScript()->appendFile(
                $view->assetUrl('js/OrbitControls.js', 'ThreeDViewer')
            );
            $view->headScript()->appendFile(
                $view->assetUrl('js/model-viewer.min.js', 'ThreeDViewer'),
                'module'
            );
            
            error_log("Added 3D viewer scripts to page for item: " . $item->id());
        }
    }
    
    /**
     * Handle media rendering based on media type
     *
     * @param \Laminas\EventManager\Event $event
     */
    public function handleMediaRender($event)
    {
        $media = $event->getTarget();
        $view = $event->getParam('view');
        $filename = $media->filename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $source = $media->source();
                
        // Check if this is a 3D model file by source filename first (most reliable)
        $is3DFile = false;
        $isStlFile = false;
        $isGlbFile = false;
        
        // Check source filename first
        if ($source) {
            if (stripos($source, '.stl') !== false) {
                $is3DFile = true;
                $isStlFile = true;
                error_log("Detected STL file by source: " . $source);
            } elseif (stripos($source, '.glb') !== false || stripos($source, '.gltf') !== false) {
                $is3DFile = true;
                $isGlbFile = true;
                error_log("Detected GLB/GLTF file by source: " . $source);
            }
        }
        
        // If not identified by source, check by file extension
        if (!$is3DFile) {
            if ($extension === 'stl' || stripos($filename, '.stl') !== false) {
                $is3DFile = true;
                $isStlFile = true;
                error_log("Detected STL file by extension: " . $extension);
            } elseif ($extension === 'glb' || $extension === 'gltf' ||
                      stripos($filename, '.glb') !== false ||
                      stripos($filename, '.gltf') !== false) {
                $is3DFile = true;
                $isGlbFile = true;
                error_log("Detected GLB/GLTF file by extension: " . $extension);
            }
        }
        
        if ($is3DFile) {
            error_log("Rendering 3D file: " . $filename);
            
            // Use the appropriate renderer
            if ($isStlFile) {
                error_log("Using STL renderer for file: " . $filename);
                $renderer = new Media\FileRenderer\StlRenderer();
                $html = $renderer->render($view, $media);
                $event->setParam('html', $html);
            } elseif ($isGlbFile) {
                error_log("Using GLB renderer for file: " . $filename);
                $renderer = new Media\FileRenderer\GlbRenderer();
                $html = $renderer->render($view, $media);
                $event->setParam('html', $html);
            } else {
                // This should never happen, but just in case
                error_log("Using Viewer3DRenderer as fallback for file: " . $filename);
                $renderer = new Media\FileRenderer\Viewer3DRenderer();
                $html = $renderer->render($view, $media);
                $event->setParam('html', $html);
            }
            
            // Stop propagation to prevent default rendering
            $event->stopPropagation();
        }
    }
    
    /**
     * Register renderers for 3D model files
     *
     * @param \Laminas\EventManager\Event $event
     */
    public function registerRenderers($event)
    {
        $services = $this->getServiceLocator();
        
        // Use the correct service name for media renderers in Omeka S
        if (!$services->has('Omeka\Media\Renderer\Manager')) {
            // If the service doesn't exist, we can't register our renderers
            return;
        }
        
        $rendererManager = $services->get('Omeka\Media\Renderer\Manager');
        
        // Register GLB renderer for GLB and GLTF files
        if (!$rendererManager->has('model/gltf-binary')) {
            $rendererManager->setRenderer('model/gltf-binary', new Media\FileRenderer\GlbRenderer());
        }
        
        // Register STL renderer for STL files
        if (!$rendererManager->has('model/stl')) {
            $rendererManager->setRenderer('model/stl', new Media\FileRenderer\StlRenderer());
        }
        
        // Register GLTF renderer
        if (!$rendererManager->has('model/gltf+json')) {
            $rendererManager->setRenderer('model/gltf+json', new Media\FileRenderer\GlbRenderer());
        }
        
        // Register for application/octet-stream (binary STL files)
        if (!$rendererManager->has('application/octet-stream')) {
            $rendererManager->setRenderer('application/octet-stream', new Media\FileRenderer\Viewer3DRenderer());
        }
        
        // Register for text/plain (ASCII STL files)
        if (!$rendererManager->has('text/plain')) {
            $rendererManager->setRenderer('text/plain', new Media\FileRenderer\Viewer3DRenderer());
        }
    }
}
