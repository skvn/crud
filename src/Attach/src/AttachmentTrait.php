<?php

namespace LaravelAttach;


use Illuminate\Database\Eloquent\Model;


trait AttachmentTrait {



    protected $attachedFiles = [];
    protected $processAttaches = [];
    public  $attachSource = 'request';



    public function getAttachedFiles()
    {
        return $this->attachedFiles;
    }


    public function setAttach($name, array $options = [])
    {

        $this->attachedFiles[$name] = AttachmentHandler::create($this, $name, $options);
    }


    public static function boot()
    {
        parent::boot();
        static::bootAttach();
    }


    public static function bootAttach()
    {


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


    public function setAttribute($key, $value)
    {



        if (array_key_exists($key, $this->attachedFiles) )
        {

            //var_dump($value);
            //var_dump($key);
            //Numeric value means we are back from handler and file ID is assigned
            //ObjectCollection means the model is filled with default values
            if ($value &&
                !is_numeric($value)
                && (!$value instanceof \Illuminate\Database\Eloquent\Collection)
            )
            {

                $this->processAttaches[$key] = $value;
                return;
            }


        }


        parent::setAttribute($key, $value);
    }


    public function getAttach($attribute)
    {
        return  $this->attachedFiles[$attribute]->getInstanceOrCollection();
    }

    public function deleteSingleAttach($args)
    {
        if (!empty($args['field']) && empty($args['id']))
        {
            //$attrValue = $this->getAttribute($args['field']);
            return $this->getAttach($args['field'])->delete();

        }

        else if (!empty($args['field']) && !empty($args['id']))
        {
             Attach::destroy([$args['id']]);
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