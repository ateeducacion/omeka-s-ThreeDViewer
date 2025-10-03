<?php

declare(strict_types=1);

namespace Viewer3DTest\Renderer;

use PHPUnit\Framework\TestCase;
use ThreeDViewer\Media\FileRenderer\GlbRenderer;
use ThreeDViewer\Media\FileRenderer\StlRenderer;
use ThreeDViewer\Media\FileRenderer\Viewer3DRenderer;
use Omeka\Media\FileRenderer\RendererInterface;

class RendererInterfaceTest extends TestCase
{
    public function testGlbRendererImplementsFileRendererInterface(): void
    {
        $this->assertInstanceOf(RendererInterface::class, new GlbRenderer());
    }

    public function testStlRendererImplementsFileRendererInterface(): void
    {
        $this->assertInstanceOf(RendererInterface::class, new StlRenderer());
    }

    public function testViewer3DRendererImplementsFileRendererInterface(): void
    {
        $this->assertInstanceOf(RendererInterface::class, new Viewer3DRenderer());
    }
}
