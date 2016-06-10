<?php

interface Control
{
    function __construct($config);//пустой
    function setModel(CrudModel $model);
    function controlTemplate();
    function controlWizardTemplate();
    function controlWidgetUrl();
    function controlCaption();
    function controlFiltrable();
    function controlValidate(); //проверка конфига

    function freshValue(); //забрать из модели
    function getValue(); //только контрол
    function setValue($value); //только контрол
    function importValue($data); //забрать свое снаружи
    function validateValue(); //???
    function syncValue(); //пихнуть в модель

    function getFilterCondition();
    function getFilterColumnName();


}



interface Form
{

}







