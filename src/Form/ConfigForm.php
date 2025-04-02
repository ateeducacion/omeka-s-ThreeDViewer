<?php
declare(strict_types=1);

namespace ThreeDViewer\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    /**
     * Initialize the form elements.
     */
    public function init(): void
    {

        $this->add([
            'name' => 'threedviewer_viewer_height',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Viewer Height (px)', // @translate
                'info' => 'Default height for the 3D viewer in pixels.', // @translate
            ],
            'attributes' => [
                'required' => false,
                'min' => 100,
                'max' => 1200,
                'value' => 500,
            ],
        ]);
        
        $this->add([
            'name' => 'threedviewer_auto_rotate',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Auto-rotate models', // @translate
                'info' => 'Enable auto-rotation for 3D models.', // @translate
            ],
            'attributes' => [
                'value' => '0',
            ],
        ]);

        $this->add([
            'name' => 'threedviewer_background_color',
            'type' => Element\Color::class,
            'options' => [
                'label' => 'Background Color', // @translate
                'info' => 'Choose the background color for 3D viewers.', // @translate
            ],
            'attributes' => [
                'required' => false,
                'value' => '#ffffff',
            ],
        ]);
        
        $this->add([
            'name' => 'threedviewer_show_grid',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Show Grid', // @translate
                'info' => 'Display a green grid to help with size perception.', // @translate
            ],
            'attributes' => [
                'value' => '0',
            ],
        ]);
    }
}
