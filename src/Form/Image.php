<?php namespace Skvn\Crud\Form;


class Image extends File {

    static $controlInfo = [
        'type' => "image",
        'template' => "crud::crud/fields/image.twig",
        'wizard_template' => "crud::wizard/blocks/fields/image.twig",
        'caption' => "Image"
    ];


}