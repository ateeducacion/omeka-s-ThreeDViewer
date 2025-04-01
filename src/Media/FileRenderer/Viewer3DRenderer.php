<?php
declare(strict_types=1);

namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;

/**
 * Renders a 3D model (e.g. GLB) in Omeka S using either model-viewer.js or three.js.
 * This is a generic renderer that delegates to the specific renderers.
 */
class Viewer3DRenderer implements RendererInterface
{
    /**
     * Render the 3D file as an embedded viewer.
     *
     * @param PhpRenderer         $view   The view renderer
     * @param MediaRepresentation $media  The media object
     * @param array               $options Additional options
     *
     * @return string HTML output
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        // Determine the file type by extension and source filename
        $filename = $media->filename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $source = $media->source();
        
        // Log information for debugging
        error_log("Viewer3DRenderer rendering file: " . $filename .
            " with extension: " . $extension . ", source: " . $source);
        
        // Check source filename first (most reliable)
        if ($source) {
            if (stripos($source, '.glb') !== false || stripos($source, '.gltf') !== false) {
                error_log("Using GLB renderer based on source filename: " . $source);
                $renderer = new GlbRenderer();
                return $renderer->render($view, $media, $options);
            }
            
            if (stripos($source, '.stl') !== false) {
                error_log("Using STL renderer based on source filename: " . $source);
                $renderer = new StlRenderer();
                return $renderer->render($view, $media, $options);
            }
        }
        
        // Then check by file extension
        if ($extension === 'glb' || $extension === 'gltf' ||
            stripos($filename, '.glb') !== false ||
            stripos($filename, '.gltf') !== false) {
            error_log("Using GLB renderer based on file extension: " . $extension);
            $renderer = new GlbRenderer();
            return $renderer->render($view, $media, $options);
        }
        
        if ($extension === 'stl' || stripos($filename, '.stl') !== false) {
            error_log("Using STL renderer based on file extension: " . $extension);
            $renderer = new StlRenderer();
            return $renderer->render($view, $media, $options);
        }
        
        // Default to STL renderer as fallback
        error_log("Using STL renderer as default fallback");
        $renderer = new StlRenderer();
        return $renderer->render($view, $media, $options);
    }
}
