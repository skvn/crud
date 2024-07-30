<?php

namespace Skvn\Crud\Form;

use Carbon\Carbon;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\FormControlCommonTrait;

class DateRange extends Field implements FormControl, FormControlFilterable
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
        }
    }

    public function getOutputValue():string
    {
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
        } else {
            if (array_key_exists($this->getFromFieldName(), $data) || array_key_exists($this->getToFieldName(), $data)) {
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
            $col = $this->getFilterColumnName();
            $from = $this->value['from'] ? $this->value['from']->timestamp : 0;
            $to = $this->value['to'] ? $this->value['to']->timestamp : 0;
            if (!empty($to)) {
                $to = strtotime(date('Y-m-d 23:59:59', $to));
            }
            if (!empty($from) && !empty($to)) {
                return ['cond' => [$col, 'BETWEEN', [$from, $to]]];
            } elseif (!empty($from)) {
                return ['cond' => [$col, '>=', $from]];
            } elseif (!empty($to)) {
                return ['cond' => [$col, '<=', $to]];
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

}
