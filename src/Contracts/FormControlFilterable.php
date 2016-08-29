<?php

namespace Skvn\Crud\Contracts;

interface FormControlFilterable
{
    public function getFilterCondition();

    public function getFilterColumnName();

    public function setFilterColumnName($col);
}
