<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Handlers\AttachmentHandler;
use Skvn\Crud\Models\CrudFile;
use Skvn\Crud\Form\Field;
use Illuminate\Database\Eloquent\Model;


trait ModelAttachmentTrait {



    protected $attachedFiles = [];
    protected $processAttaches = [];
    public  $attachSource = 'request';



    public function getAttachedFiles()
    {
        return $this->attachedFiles;
    }

    function attachAppendConfig()
    {
        if (!empty($this->config['fields']))
        {
            foreach ($this->config['fields'] as $name => $field)
            {
                if (!empty($field['type']) && in_array($field['type'], [Field :: FILE, Field :: IMAGE]))
                {
                    $this->setAttach($name, $field);
                    $this->fillable[] = $name;
                }
            }
        }
    }


    public function setAttach($name, array $options = [])
    {
        $this->attachedFiles[$name] = AttachmentHandler::create($this, $name, $options);
    }


    public static function bootModelAttachmentTrait()
    {

        static::registerPostconstruct(function($instance){
            $instance->attachAppendConfig();
        });

        static::registerSetter(function ($instance, $key, $value){
            return $instance->attachSetAttribute($key, $value);
        });

        static::deleting(function($instance) {
            foreach($instance->attachedFiles as $attachedFile) {
                $attachedFile->deleteAll(false);
            }
        });

        static::saved(function(Model $instance) {
            if ($instance->processAttaches) {
                foreach ($instance->processAttaches as $k => $v) {
                    $attachedFile = $instance->attachedFiles[$k];
                    if ($instance->attachSource == 'request') {
                        $attachedFile->setUploadedFile($v);
                        $attachedFile->processTitles();
                    } else if ($instance->attachSource == 'fs') {
                        $attachedFile->setFsFile($v);
                    }
                }
            }
        });
    }


    public function attachSetAttribute($key, $value)
    {
        if (array_key_exists($key, $this->attachedFiles) )
        {
            //don't delete  when file is not altered
            if ($value === '')
            {
                return true;
            }
            //Numeric value means we are back from handler and file ID is assigned
            //ObjectCollection means the model is filled with default values
            if ($value &&
                !is_numeric($value)
                && (!$value instanceof \Illuminate\Database\Eloquent\Collection)
            )
            {
                $this->processAttaches[$key] = $value;
                return true;
            }
        }
//        parent::setAttribute($key, $value);
    }

//    function getAttribute($key)
//    {
//        if (array_key_exists($key, $this->attachedFiles) )
//        {
//            return $this->getAttach($key);
//        }
//        return parent :: getAttribute($key);
//    }


    public function getAttach($attribute)
    {
        return  $this->attachedFiles[$attribute]->getInstanceOrCollection();
    }

    function hasAttach($attribute)
    {
        return array_key_exists($attribute, $this->attachedFiles);
    }


    public function deleteSingleAttach($args)
    {
        if (!empty($args['field']) && empty($args['id']))
        {
            $deleted = $this->getAttach($args['field'])->delete();
            //$attrValue = $this->getAttribute($args['field']);
            $this->setAttribute($this->attachedFiles[$args['field']]->getOption('field'),0);
            $this->save();
            return $deleted;
        }
        else if (!empty($args['field']) && !empty($args['id']))
        {
             CrudFile::destroy([$args['id']]);
             $meth = $args['field'];
             $this->$meth()->detach($args['id']);
        }
    }


    public  function getAttachOptions()
    {
        $ret = [];
        foreach ($this->attachedFiles as $attach)
        {
            $inst  = $attach->getInstanceOrCollection();
            if ($inst instanceof \Illuminate\Database\Eloquent\Collection)
            {
                foreach ($inst as $file) {
                    $ret[] = ['value'=>$file->getAttribute('download_link'),'text'=>$file->getAttribute('title')];
                }
            } else {
                //$ret[] = ['value'=>$inst->getAttribute('download_link'),'text'=>$inst->getAttribute('title')];
            }
        }

        return $ret;
    }

    public function nullifyAttachQueue()
    {
        $this->processAttaches = null;
    }



} 