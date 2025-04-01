<?php
declare(strict_types=1);

namespace ThreeDViewer;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
            Form\SiteSettingsFieldset::class => Form\SiteSettingsFieldset::class,
        ],
    ],
    'media_renderers' => [
        'factories' => [
            'model/stl' => function () {
                return new Media\FileRenderer\StlRenderer();
            },
            'model/gltf-binary' => function () {
                return new Media\FileRenderer\GlbRenderer();
            },
            'model/gltf+json' => function () {
                return new Media\FileRenderer\GlbRenderer();
            },
            'application/octet-stream' => function () {
                return new Media\FileRenderer\Viewer3DRenderer();
            },
            'binary/octet-stream' => function () {
                return new Media\FileRenderer\Viewer3DRenderer();
            },
            'application/x-binary' => function () {
                return new Media\FileRenderer\Viewer3DRenderer();
            },
            'text/plain' => function () {
                return new Media\FileRenderer\Viewer3DRenderer();
            },
            'file' => function () {
                return new Media\FileRenderer\Viewer3DRenderer();
            },
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
        ],
        'site_settings' => [
            'threedviewer_site_library' => 'global',
            'threedviewer_site_viewer_height' => null,
            'threedviewer_site_auto_rotate' => null,
        ],
    ],
];
