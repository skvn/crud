<?php

namespace Skvn\Crud\Form;

class Radio extends Select
{
    public function controlType():string
    {
        return 'radio';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.radio';
    }

    public function controlWidgetUrl():string
    {
        return '';
    }
}
