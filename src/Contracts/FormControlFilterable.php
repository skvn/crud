<?php namespace Skvn\Crud\Contracts;

interface FormControlFilterable
{
    function getFilterCondition();
    function getFilterColumnName();
    function setFilterColumnName($col);
}