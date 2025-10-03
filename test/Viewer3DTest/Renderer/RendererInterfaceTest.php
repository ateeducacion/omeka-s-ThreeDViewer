<?php

declare(strict_types=1);

namespace Viewer3DTest\Renderer;

use PHPUnit\Framework\TestCase;
use ThreeDViewer\Media\FileRenderer\Viewer3DRenderer;
use ThreeDViewer\Media\FileRenderer\BabylonRenderer;
use Omeka\Media\FileRenderer\RendererInterface;

class RendererInterfaceTest extends TestCase
{
    public function testBabylonRendererImplementsFileRendererInterface(): void
    {
        $this->assertInstanceOf(RendererInterface::class, new BabylonRenderer());
    }

    public function testViewer3DRendererImplementsFileRendererInterface(): void
    {
        $this->assertInstanceOf(RendererInterface::class, new Viewer3DRenderer());
    }
}
