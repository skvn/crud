<?php namespace Skvn\Crud\Handlers;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Skvn\Crud\Models\CrudFile;


class AttachmentHandler {


    protected $parentInstance;
    protected $selfInstance;
    protected $relationInstances;
    protected $parentPropName;
    protected $multi = false;
    protected $currentInstanceId;
    protected $processedIds = [];

    public function __construct($parentInstance,$propName, $options=[])
    {
        $this->parentInstance = $parentInstance;
        $this->parentPropName = $propName;
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
        $titles = \Request::get($this->parentPropName.'_title');
        if (!empty($titles[$key]))
        {
            return $titles[$key];
        }
    }
    protected function processUploadedFile(UploadedFile $uploadedFile)
    {


        $ret = self::generateFdata($uploadedFile);
        $name = uniqid();
        $uploadedFile->move(\Config::get('attach.root'),$name);
        $ret['originalPath'] = \Config::get('attach.root').DIRECTORY_SEPARATOR.$name;

        return $ret;

    }

    protected function processFsFile(File $file)
    {

        $ret = self::generateFdata($file);
        $name = uniqid();
        $file->move(\Config::get('attach.root'),$name);
        $ret['originalPath'] = \Config::get('attach.root').DIRECTORY_SEPARATOR.$name;

        return $ret;

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
            } else {
                $instance = CrudFile::findOrNew($k);
            }
            if (!empty($file['originalPath'])) {


                if ($instance->path) {
                    if (file_exists($instance->path)) {
                        unlink($instance->path);
                    }
                }

                $newDest = self::generateSaveFilename($file);
                \File::makeDirectory(dirname($newDest), 0755, true, true);
                \File::move($file['originalPath'], $newDest);

                $instance->fill(['file_name' => $file['originalName'],
                    'mime_type' => $file['originalMime'],
                    'file_size' => $file['originalSize'],
                    'title' => (!empty($file['title'])?$file['title']:''),
                    'path' => $newDest
                ]);
                $instance->save();

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

        if ($instance->path)
        {
            if (file_exists($instance->path))
            {
                unlink($instance->path);
            }
        }

        $instance->delete();

    }


    function initSelfInstance()
    {
        $prop = $this->parentPropName;
        if (!$this->multi)
        {
            if (!$this->selfInstance)
            {

                $this->selfInstance = CrudFile::findOrNew($this->parentInstance->$prop);
            }
        } else {

            $this->selfInstance = CrudFile::findOrNew($this->currentInstanceId);

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


    static  function generateFdata($file)
    {
        $fdata = [];
        if ($file instanceof UploadedFile)
        {
            $fdata['originalName'] =   $file->getClientOriginalName();
            $fdata['originalExt']  = $file->getClientOriginalExtension();
            $fdata['originalMime'] =  $file->getClientMimeType();

        } else {
            $fdata['originalName'] = $file->getBasename();
            $fdata['originalExt'] = $file->getExtension();
            $fdata['originalMime'] = $file->getMimeType();

        }
        $fdata['originalSize'] = $file->getSize();

        return $fdata;
    }
    static function  generateSaveFilename($file)
    {

        $md5 = md5($file['originalName']);
        $level1 = substr($md5,0,2);
        $level2 = substr($md5,2,2);
        return  join(DIRECTORY_SEPARATOR,array(\Config::get('attach.root'),$level1,$level2,uniqid().'.'.$file['originalExt']));

    }

    public function processTitles()
    {
        $titles = \Request::get($this->parentPropName.'_title');
        if ($titles && is_array($titles))
        {
            foreach ($titles as $k=>$v)
            {
                if ($k>0 && !in_array($k,$this->processedIds))
                {

                    $obj = CrudFile::find($k);

                    $obj->update(['title'=>$v]);
                }
            }
        }
    }







}