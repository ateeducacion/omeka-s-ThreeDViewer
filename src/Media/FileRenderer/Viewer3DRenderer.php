<?php
declare(strict_types=1);
namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\FileRenderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;

use Omeka\Media\Renderer\Manager;

/**
 * Renders a 3D model (e.g. GLB) in Omeka S using either model-viewer.js or three.js.
 * This is a generic renderer that delegates to the specific renderers only for 3D files.
 */
class Viewer3DRenderer implements RendererInterface
{

    /**
     * Render the file either as a 3D viewer or delegate to the original renderer.
     *
     * @param PhpRenderer         $view    The view renderer
     * @param MediaRepresentation $media   The media object
     * @param array               $options Additional options
     *
     * @return string HTML output
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []): string
    {
        if (!$this->is3DFile($media)) {
            // Get URLs correctly using view helpers
            $fileUrl = $media->originalUrl();
            $fileName = pathinfo($fileUrl, PATHINFO_BASENAME);
            
            // Use the view helper for the default thumbnail URL
            $thumbnailUrl = $view->assetUrl('thumbnails/default.png', 'Omeka');
            
            $html = '<div class="media-render file">';
            $html .= '<a href="' . $fileUrl . '" title="' . htmlspecialchars($fileName) . '">';
            $html .= '<img src="' . $thumbnailUrl . '" alt="">';
            $html .= '</a></div>';
            
            return $html;
        }

        $filename = $media->filename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        error_log("Processing 3D file: $filename");

        if ($extension === 'stl') {
            error_log("Using STL renderer for: $filename");
            $renderer = new StlRenderer();
        } else {
            error_log("Using GLB renderer for: $filename");
            $renderer = new GlbRenderer();
        }
        return $renderer->render($view, $media, $options);
    }

    /**
     * Determine if the media file is a 3D model based on its extension.
     *
     * @param MediaRepresentation $media The media object
     *
     * @return bool True if the file is 3D, false otherwise.
     */
    private function is3DFile(MediaRepresentation $media): bool
    {
        $filename = $media->filename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $is3D = in_array($extension, ['stl', 'glb', 'gltf'], true);

        return $is3D;
    }
}
