<?php namespace Skvn\Crud\Contracts;

interface FormControl
{
    function controlType():string;
    function controlWidgetUrl():string;
    function controlTemplate():string;
    function controlValidateConfig():bool;
    function pullFromModel();
    function pushToModel();
    function pullFromData(array $data);
    function getValue();
    function getOutputValue():string;
    function setValue($value);
}