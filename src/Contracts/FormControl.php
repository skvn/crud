<?php

namespace Skvn\Crud\Contracts;

interface FormControl
{
    public function controlType():string;

    public function controlWidgetUrl():string;

    public function controlTemplate():string;

    public function controlValidateConfig():bool;

    public function pullFromModel();

    public function pushToModel();

    public function pullFromData(array $data);

    public function getValue();

    public function getOutputValue():string;

    public function setValue($value);
}
