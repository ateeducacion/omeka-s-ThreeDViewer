<?php
declare(strict_types=1);

namespace ThreeDViewer\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class Sketchfab extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'three_d_viewer_sketchfab_uid',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Sketchfab Model UID',
                'info' => 'Enter the UID of the Sketchfab model to display.',
            ],
            'attributes' => [
                'id' => 'three_d_viewer_sketchfab_uid',
            ],
        ]);
    }
}