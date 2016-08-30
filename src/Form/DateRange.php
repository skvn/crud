<?php

namespace Skvn\Crud\Form;

use Carbon\Carbon;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\FormControlCommonTrait;

class DateRange extends Field implements FormControl
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        if (! empty($this->config['fields'])) {
            $this->value = [
                'from' => $this->model->getAttribute($this->config['fields'][0]),
                'to'   => $this->model->getAttribute($this->config['fields'][1]),
            ];
            if ($this->value['from'] && $this->value['from']->timestamp < 10) {
                $this->value['from'] = null;
            }
            if ($this->value['to'] && $this->value['to']->timestamp < 10) {
                $this->value['to'] = null;
            }
            //$this->value = $this->model->getAttribute($this->config['fields'][0]) . "~" . $this->model->getAttribute($this->config['fields'][1]);
        }
    }

    public function getOutputValue():string
    {
        //return date($this->config['format'], $this->getValueFrom()) . "-" . date($this->config['format'], $this->getValueTo());
        return $this->getValueFrom()->format($this->config['format']).'-'.$this->getValueTo()->format($this->config['format']);
    }

    public function pushToModel()
    {
        $this->model->setAttribute($this->getFromFieldName(), $this->getValueFrom());
        $this->model->setAttribute($this->getToFieldName(), $this->getValueTo());
    }

    public function pullFromData(array $data)
    {
        if (! empty($data[$this->name]) && strpos($data[$this->name], '~') !== false) {
            $split = explode('~', $data[$this->name]);
            $this->value = [
                'from' => Carbon :: parse($split[0]),
                'to'   => Carbon :: parse($split[1]),
            ];
            //$this->value = $data[$this->name];
        } else {
            if (isset($data[$this->getFromFieldName()]) || isset($data[$this->getToFieldName()])) {
                if (! empty($data[$this->getFromFieldName()])) {
                    $this->value['from'] = Carbon :: parse($data[$this->getFromFieldName()]);
                } else {
                    $this->value['from'] = null;
                }
                if (! empty($data[$this->getToFieldName()])) {
                    $this->value['to'] = Carbon :: parse($data[$this->getToFieldName()]);
                } else {
                    $this->value['to'] = null;
                }
            }
        }
    }

    public function getFromFieldName()
    {
        if (! empty($this->config['fields'])) {
            return $this->config['fields'][0];
        }

        return $this->name.'_from';
    }

    public function getToFieldName()
    {
        if (! empty($this->config['fields'])) {
            return $this->config['fields'][1];
        }

        return $this->name.'_to';
    }

    public function getValueFrom()
    {
        return $this->value['from'] ?? null;
    }

    public function getValueTo()
    {
        return $this->value['to'] ?? null;
    }

    public function getDefaultFrom()
    {
        if (! empty($this->config['default']) && strpos($this->config['default'], '~') !== false) {
            return explode('~', $this->config['default'])[0];
        }
    }

    public function getDefaultTo()
    {
        if (! empty($this->config['default']) && strpos($this->config['default'], '~') !== false) {
            return explode('~', $this->config['default'])[1];
        }
    }

    public function getFilterCondition()
    {
        if (! empty($this->value)) {
            $split = explode('~', $this->value);
            $col = $this->getFilterColumnName();
            if ($split[0] != '' && $split[1] != '') {
                return ['cond' => [$col, 'BETWEEN', $split]];
            } elseif ($split[0] != '') {
                return ['cond' => [$col, '>=', $split[0]]];
            } elseif ($split[1] != '') {
                return ['cond' => [$col, '=<', $split[1]]];
            }
        }
    }

    public function configureModel(CrudModel $model, array $config)
    {
        $model->setDates($config['fields']);

        return $config;
    }

    public function controlType():string
    {
        return 'date_range';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.date_range';
    }

//
}
