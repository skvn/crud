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
//        $ret = self::generateFdata($uploadedFile);
//        $name = str_replace(".", "_", uniqid(!empty($this->options['prefix']) ? $this->options['prefix'] : "attach", true));
//        $target = $this->app['config']->get("attach.root") . DIRECTORY_SEPARATOR . "tmp";
////        if (!empty($this->options['path']))
////        {
////            $target .= DIRECTORY_SEPARATOR . $this->options['path'];
////        }
//        if (!file_exists($target))
//        {
//            $this->app['files']->makeDirectory($target, 0755, true, true);
//        }
//        //$target .= "/" . $name;
//        //$uploadedFile->move(\Config::get('attach.root'),$name);
//        $uploadedFile->move($target, $name);
//        //$ret['originalPath'] = \Config::get('attach.root').DIRECTORY_SEPARATOR.$name;
//        $ret['originalPath'] = $target.DIRECTORY_SEPARATOR.$name;
//
//        return $ret;

    }

    protected function processFsFile(File $file)
    {
        return $this->createAttachInstance()->attachStoreTmpFile($file);
//        $ret = self::generateFdata($file);
//        $name = uniqid();
//        $target = $this->app['config']->get('attach.root') . DIRECTORY_SEPARATOR . 'tmp';
//        if (!file_exists($target))
//        {
//            $this->app['files']->makeDirectory($target, 0755, true, true);
//        }
//        $file->move($target, $name);
//        $ret['originalPath'] = $target.DIRECTORY_SEPARATOR.$name;
//
//        return $ret;
    }

//    protected function storeTmpFile($file)
//    {
//        $instance = $this->createAttachInstance();
//        return $instance->attachStoreTmpFile($file);
//        $class = $this->getFileClass();
//        $instance = new $class();
//        $instance->setAttachOptions($this->options);
        //$ret = self::generateFdata($file);
//        $ret = $instance->attachCreateFileInfo($file);
//        $name = str_replace(".", "_", uniqid('tmp', true));
//        $target = $this->app['config']->get("attach.root") . DIRECTORY_SEPARATOR . "tmp";
//        if (!file_exists($target))
//        {
//            $this->app['files']->makeDirectory($target, 0755, true, true);
//        }
//        $file->move($target, $name);
//        $ret['originalPath'] = $target.DIRECTORY_SEPARATOR.$name;
//        $ret['fileObj'] = new File($ret['originalPath']);
//        return $ret;
//    }


    function save($files)
    {
        $this->initSelfInstance();
        $prop = $this->parentPropName;
        if ($this->multi)
        {
            $ids = $this->parentInstance->$prop->lists('id')->all();
        }
        //$class = $this->getFileClass();

        foreach ($files as $k=> $file)
        {
            if (!$this->multi)
            {
                $instance = $this->selfInstance;
            }
            else
            {
                $instance = $this->createAttachInstance($k);
                //$instance = $class::findOrNew($k);
            }
            if (!empty($file['originalPath']))
            {
                //$this->options['instance_id'] = $this->parentInstance->id;
                //$instance->setAttachOptions($this->options);
                //$instance->attachStoreFile($file, array_merge($this->options, ['instance_id' => $this->parentInstance->id]));
                $instance->attachStoreFile($file, $this->parentInstance->getFilesConfig($file['originalName']));

                if (!$this->multi) {
                   // $this->parentInstance->setAttribute($this->parentPropName, $instance->id);
                   // $this->parentInstance->save();

                    $this->parentInstance->nullifyAttachQueue();
                    $this->parentInstance->update([$this->parentPropName=>$instance->id]);
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
            $this->parentInstance->setAttribute($this->parentPropName, null);
            $this->parentInstance->save();
        }

    }

    function deleteSingleInstance($instance)
    {
//        if ($instance->path)
//        {
//            if (file_exists($instance->getFilePath()))
//            {
//                unlink($instance->getFilePath());
//            }
//        }
        $instance->delete();
    }


    function initSelfInstance()
    {
        $prop = $this->parentPropName;
        if (!$this->multi)
        {
            if (!$this->selfInstance)
            {
                $this->selfInstance = $this->createAttachInstance($this->parentInstance->$prop);
                //$this->selfInstance = $class::findOrNew($this->parentInstance->$prop);
                //$this->selfInstance->setAttachOptions($this->options);
            }
        } else {
            $this->selfInstance = $this->createAttachInstance($this->currentInstanceId);
            //$this->selfInstance = $class::findOrNew($this->currentInstanceId);
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


//    static  function generateFdata($file)
//    {
//        $fdata = [];
//        if ($file instanceof UploadedFile)
//        {
//            $fdata['originalName'] =   $file->getClientOriginalName();
//            $fdata['originalExt']  = $file->getClientOriginalExtension();
//            $fdata['originalMime'] =  $file->getClientMimeType();
//
//        } else {
//            $fdata['originalName'] = $file->getBasename();
//            $fdata['originalExt'] = $file->getExtension();
//            $fdata['originalMime'] = $file->getMimeType();
//
//        }
//        $fdata['originalSize'] = $file->getSize();
//        $fdata['fileObj'] = $file;
//
//        return $fdata;
//    }
//    static function  generateSaveFilename($file)
//    {
//
//        $md5 = md5($file['originalName']);
//        $level1 = substr($md5,0,2);
//        $level2 = substr($md5,2,2);
//        return  join(DIRECTORY_SEPARATOR,array(\Config::get('attach.root'),$level1,$level2,uniqid().'.'.$file['originalExt']));
//
//    }

    public function processTitles()
    {
        //$class = $this->getFileClass();

        $titles = $this->app['request']->get($this->parentPropName.'_title');
        if ($titles && is_array($titles))
        {
            foreach ($titles as $k=>$v)
            {
                if ($k>0 && !in_array($k,$this->processedIds))
                {
                    $obj = $this->createAttachInstance($k, true);
                    //$obj = $class::find($k);

                    $obj->update(['title'=>$v]);
                }
            }
        }
    }

//    protected function getFileClass()
//    {
//        return !empty($this->options['model']) ? CrudModel :: resolveClass($this->options['model']) : CrudFile :: class;
//    }

    protected function createAttachInstance($id = null, $exists = false)
    {
        $class = $this->parentInstance->getFilesConfig("any", "class");
        //$class = $this->parentInstance->getFilesConfig("any", "model") ? CrudModel :: resolveClass($this->parentInstance->getFilesConfig("any", "model")) : CrudFile :: class;
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