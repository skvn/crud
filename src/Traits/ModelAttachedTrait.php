<?php namespace Skvn\Crud\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;


trait ModelAttachedTrait {

    protected $attach_options = [];

    function setAttachOptions($opts)
    {
        $this->attach_options = $opts;
    }

    public static function bootModelAttachedTrait()
    {
        static::deleting(function($instance) {
            if (file_exists($instance->attachGetPath()))
            {
                unlink($instance->attachGetPath());
            }
        });
    }

    function attachStoreFile($fileInfo)
    {
        if ($this->path) {
            if (file_exists($this->attachGetPath()))
            {
                unlink($this->attachGetPath());
            }
        }
        $newDest = $this->attachCreateFilename($fileInfo);
        $this->app['files']->makeDirectory(dirname($newDest), 0755, true, true);
        //$this->app['files']->move($fileInfo['originalPath'], $newDest);
        $fileInfo['fileObj']->move(dirname($newDest), basename($newDest));

        $this->forceFill(['file_name' => $fileInfo['originalName'],
            'mime_type' => $fileInfo['originalMime'],
            'file_size' => $fileInfo['originalSize'],
            'title' => (!empty($fileInfo['title']) ? $fileInfo['title'] : ''),
            'path' => basename($newDest)
        ]);
        $this->save();
    }

    function attachGetPath()
    {
        $path = $this->path;
        if (strpos($path, '/') === 0)
        {
            return $path;
        }
        $fileInfo = [
            'originalName' => $this->file_name,
            'filename' => $this->path
        ];
        $path = $this->attachCreateFilename($fileInfo);
        //var_dump($path);
        //var_dump($this->attach_options);
        return $path;
    }

    function  attachCreateFilename($fileInfo)
    {
        $parts = [];
        $parts[] = $this->app['config']->get('attach.root');
        $path = !empty($this->attach_options['path']) ? $this->attach_options['path'] : '%l1/%l2';
        $md5 = md5($fileInfo['originalName']);
        $path = str_replace('%l1', substr($md5,0,2), $path);
        $path = str_replace('%l2', substr($md5,2,2), $path);
        if(!empty($this->attach_options['instance_id']))
        {
            $path = str_replace('%i3', str_pad($this->attach_options['instance_id'] % 1000, 3, '0', STR_PAD_LEFT), $path);
            $path = str_replace('%id', $this->attach_options['instance_id'], $path);
        }
        $parts[] = $path;
        if (!empty($fileInfo['filename']))
        {
            $parts[] = $fileInfo['filename'];
        }
        else
        {
            $parts[] = str_replace(".", "_", uniqid(!empty($this->attach_options['prefix']) ? $this->attach_options['prefix'] : 'img', true)) . "." . $fileInfo['originalExt'];
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    function attachCreateFileInfo($file)
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
        $fdata['fileObj'] = $file;

        return $fdata;
    }

    public function getDownloadLinkAttribute()
    {
        $symlink = $this->app['config']->get('attach.symlink');
        if (!empty($symlink))
        {
            return '/'.str_replace($this->app['config']->get('attach.root'), $symlink,$this->path);
        }
        return \URL::route('download_attach',array('id' => $this->id, 'filename'=>urlencode($this->file_name)));
    }



    public function getTitleAttribute()
    {
        if (empty($this->attributes['title']))
        {
            return $this->file_name;
        } else {
            return $this->attributes['title'];
        }
    }







}