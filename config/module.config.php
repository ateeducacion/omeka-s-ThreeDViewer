<?php
declare(strict_types=1);

namespace ThreeDViewer;

use ThreeDViewer\Media\FileRenderer\Viewer3DRenderer;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
    ],

    'assets' => [
        'use_externals' => false,
        'externals' => [
            'ThreeDViewer' => [
                'vendor/model-viewer/model-viewer.min.js' =>
                    'https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js',
                'vendor/three/three.min.js' =>
                    'https://cdn.jsdelivr.net/npm/three@0.120.1/build/three.min.js',
                'vendor/three/STLLoader.js' =>
                    'https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/loaders/STLLoader.js',
                'vendor/three/OrbitControls.js' =>
                    'https://cdn.jsdelivr.net/npm/three@0.120.1/examples/js/controls/OrbitControls.js',
            ],
        ],
    ],

    'file_renderers' => [
        'invokables' => [
            'stl_renderer' => Media\FileRenderer\StlRenderer::class,
            'glb_renderer' => Media\FileRenderer\GlbRenderer::class,
            'viewer3d_renderer' => Media\FileRenderer\Viewer3DRenderer::class,
        ],
        'aliases' => [
            'model/stl' => 'stl_renderer',
            'model/gltf-binary' => 'glb_renderer',
            'model/gltf+json' => 'glb_renderer',
            'application/octet-stream' => 'viewer3d_renderer',
            'binary/octet-stream' => 'viewer3d_renderer',
            'application/x-binary' => 'viewer3d_renderer',
            // These aliases map MIME types and common identifiers to the appropriate renderer.
            // The alias for 'text/plain' allows rendering of 3D files that are correctly named
            // (e.g. .stl or .glb) but incorrectly labeled with a generic MIME type.
            // The renderer will rely on the file extension in these cases.
            'text/plain' => 'viewer3d_renderer',
            'stl' => 'stl_renderer',
            'glb' => 'glb_renderer',
            'gltf' => 'glb_renderer',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'threedviewer' => [
        'settings' => [
            'threedviewer_default_library' => 'model-viewer',
            'threedviewer_viewer_height' => 500,
            'threedviewer_auto_rotate' => true,
            'threedviewer_show_grid' => false,
        ]
    ],
];
