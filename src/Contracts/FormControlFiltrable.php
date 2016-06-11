<?php namespace Skvn\Crud\Contracts;

interface FormControlFiltrable
{
    function getFilterCondition();
    function getFilterColumnName();
}