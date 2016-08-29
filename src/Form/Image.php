<?php

namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;

class Image extends File implements FormControl
{
    public function controlType():string
    {
        return 'image';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.image';
    }
}
