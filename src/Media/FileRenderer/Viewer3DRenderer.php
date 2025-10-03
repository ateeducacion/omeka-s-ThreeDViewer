<?php
declare(strict_types=1);
namespace ThreeDViewer\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\FileRenderer\RendererInterface;
use Laminas\View\Renderer\PhpRenderer;

/**
 * Renders 3D models in Omeka S using the Babylon.js renderer for every supported format.
 * This class keeps backwards compatibility with Omeka's renderer manager by delegating
 * non-3D media back to the default file render behaviour.
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

        $library = $this->getDefaultLibrary($view);
        $filename = $media->filename();

        if ($library === 'babylon') {
            error_log("Rendering 3D file with Babylon.js: $filename");
            $renderer = new BabylonRenderer();
        } else {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension === 'stl') {
                error_log("Rendering 3D file with Three.js STL viewer: $filename");
                $renderer = new StlRenderer();
            } else {
                error_log("Rendering 3D file with model-viewer: $filename");
                $renderer = new GlbRenderer();
            }
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

    private function getDefaultLibrary(PhpRenderer $view): string
    {
        $default = 'model-viewer';

        try {
            $setting = $view->plugin('setting');
            $library = (string) $setting('threedviewer_default_library', $default);

            return in_array($library, ['model-viewer', 'babylon'], true) ? $library : $default;
        } catch (\Throwable $e) {
            error_log('Error getting viewer library: ' . $e->getMessage());

            return $default;
        }
    }
}
