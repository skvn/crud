<?php namespace Skvn\Crud\Contracts;

interface FormControl
{
    function controlType();
    function controlWidgetUrl();
    function controlTemplate();
}