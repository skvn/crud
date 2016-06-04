<?php namespace Skvn\Crud\Handlers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Skvn\Crud\Models\CrudFile;
use Skvn\Crud\Models\CrudModel;
use Illuminate\Container\Container;


class AttachmentHandler {


    protected $parentInstance;
    protected $selfInstance;
    protected $relationInstances;
    protected $parentPropName;
    protected $parentFieldName;
    protected $multi = false;
    protected $currentInstanceId;
    protected $processedIds = [];
    protected $app;
    protected $options = [];

    public function __construct($parentInstance,$propName, $options=[])
    {
        $this->app = Container :: getInstance();
        $this->parentInstance = $parentInstance;
        $this->parentPropName = $propName;
        $this->parentFieldName = $options['field'];
        $this->options = $options;
        if (isset($options['multi']))
        {
            $this->multi = $options['multi'];
        }
    }

    public static function create($parentInstance, $propName, $options=[])
    {
        return new self($parentInstance,$propName, $options);
    }

    function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    public function setUploadedFile($uploadedFile)
    {
        $files = [];
        if (is_array($uploadedFile))
        {
            foreach($uploadedFile as $k=>$f)
            {
                if ($f instanceof UploadedFile) {
                    $files[$k] = $this->processUploadedFile($f);
                    $files[$k]['title'] = $this->processUploadedTitle($k);
                    $this->processedIds[] = $k;
                }
            }
        }
        else if ($uploadedFile instanceof UploadedFile)
        {
            $files[] = $this->processUploadedFile($uploadedFile);
        }

        $this->save($files);
    }

    public function setFsFile($file)
    {
        $files = [];
        if (is_array($file))
        {
            foreach($file as $k=>$f)
            {
                if ($f instanceof File) {
                    $files[$k] = $this->processFsFile($f);
                    $files[$k]['title'] = $f->getBasename('.'.$f->getExtension());
                    $this->processedIds[] = $k;
                }
            }
        }
        else if ($file instanceof File)
        {
            $files[] = $this->processFsFile($file);
        }

        $this->save($files);
    }

    protected  function processUploadedTitle($key)
    {
        $titles = $this->app['request']->get($this->parentPropName.'_title');
        if (!empty($titles[$key]))
        {
            return $titles[$key];
        }
    }

    protected function processUploadedFile(UploadedFile $uploadedFile)
    {
        return $this->createAttachInstance()->attachStoreTmpFile($uploadedFile);
    }

    protected function processFsFile(File $file)
    {
        return $this->createAttachInstance()->attachStoreTmpFile($file);
    }

    function save($files)
    {
        $this->initSelfInstance();
        $prop = $this->parentPropName;
        if ($this->multi)
        {
            $ids = $this->parentInstance->$prop->lists('id')->all();
        }

        foreach ($files as $k=> $file)
        {
            if (!$this->multi)
            {
                $instance = $this->selfInstance;
            }
            else
            {
                $instance = $this->createAttachInstance($k);
            }
            if (!empty($file['originalPath']))
            {
                $instance->attachStoreFile($file, $this->parentInstance->getFilesConfig($file['originalName']));

                if (!$this->multi) {
                    $this->parentInstance->nullifyAttachQueue();
                    $this->parentInstance->update([$this->parentFieldName => $instance->id]);
                } else {
                    if (!in_array($instance->id,$ids))
                    {
                        $ids[] =  $instance->id;
                    }
                }
            }
        }

        if ($this->multi) {
            if (count($ids)) {
                $this->parentInstance->$prop()->sync($ids);
            }
        }
    }

    function deleteAll($parentSave = true)
    {
        $this->initSelfInstance();
        if (!$this->multi)
        {
            $this->deleteSingleInstance($this->selfInstance);
        } else {
            $prop = $this->parentPropName;
            $this->initSelfInstance();
            $relationInstances = $this->parentInstance->$prop;
            if ($relationInstances && is_array($relationInstances)) {

                foreach ($relationInstances as $i) {
                    $this->deleteSingleInstance($i);
                }
            }
        }

        if ($parentSave) {
            $this->parentInstance->setAttribute($this->parentFieldName, null);
            $this->parentInstance->save();
        }

    }

    function deleteSingleInstance($instance)
    {
        $instance->delete();
    }


    function initSelfInstance()
    {
        $field = $this->parentFieldName;
        if (!$this->multi)
        {
            if (!$this->selfInstance)
            {
                $this->selfInstance = $this->createAttachInstance($this->parentInstance->$field);
            }
        } else {
            $this->selfInstance = $this->createAttachInstance($this->currentInstanceId);
        }
    }

    public function getInstanceOrCollection()
    {
        $prop = $this->parentPropName;

        if ($this->multi) {

            return $this->parentInstance->$prop;
        } else {
            $this->initSelfInstance();
            return $this->selfInstance;
        }
    }

    public function processTitles()
    {
        $titles = $this->app['request']->get($this->parentPropName.'_title');
        if ($titles && is_array($titles))
        {
            foreach ($titles as $k=>$v)
            {
                if ($k>0 && !in_array($k,$this->processedIds))
                {
                    $obj = $this->createAttachInstance($k, true);
                    $obj->update(['title'=>$v]);
                }
            }
        }
    }

    protected function createAttachInstance($id = null, $exists = false)
    {
        $class = $this->parentInstance->getFilesConfig("any", "class");
        if (is_null($id))
        {
            $instance = new $class();
        }
        else
        {
            if ($exists)
            {
                $instance = $class :: find($id);
            }
            else
            {
                $instance = $class :: findOrNew($id);
            }
        }
        return $instance;
    }







}