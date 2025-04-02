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
     * @var RendererManager
     */
    protected $rendererManager;

    /**
     * Constructor.
     *
     * @param RendererManager $rendererManager
     */
    public function __construct(Manager $rendererManager = null)
    {
        $this->rendererManager = $rendererManager;
    }

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
            // Get the default renderer based on the media type.
            try {
                $origRenderer = $this->rendererManager->get($media->mediaType());
                // Pass the $view parameter as the first argument
                // die();
                                error_log("por aqui si pasa al cargar un jpg");

                return $origRenderer->render($view, $media, $options);
            } catch (\Laminas\ServiceManager\Exception\ServiceNotFoundException $e) {
                // Use commented-out fallback code, but correctly
                error_log("por aqui no pasa al cargar un jpg");
                $fallback = new \Omeka\Media\Renderer\Fallback();
                return $fallback->render($view, $media, $options);
            }
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

        // Check by source filename if provided
        $source = $media->source();
        if (!$is3D && $source) {
            $sourceExtension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            $is3D = in_array($sourceExtension, ['stl', 'glb', 'gltf'], true);
        }

        return $is3D;
    }
}
