<?php namespace Skvn\Crud\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;


trait ModelAttachedTrait {



    public static function bootModelAttachedTrait()
    {
        static::deleting(function($instance) {
            if (file_exists($instance->attachGetPath()))
            {
                unlink($instance->attachGetPath());
            }
        });
    }

    function attachStoreFile($fileInfo, $args = [])
    {
        if ($this->path) {
            if (file_exists($this->attachGetPath()))
            {
                unlink($this->attachGetPath());
            }
        }
        $newPath = $this->attachCreateFilename($fileInfo, $args);
        $newDest = $this->app['config']->get('attach.root') . DIRECTORY_SEPARATOR . $newPath;
        $this->app['files']->makeDirectory(dirname($newDest), 0755, true, true);
        //$this->app['files']->move($fileInfo['originalPath'], $newDest);
        $fileInfo['fileObj']->move(dirname($newDest), basename($newDest));

        $this->forceFill(['file_name' => $fileInfo['originalName'],
            'mime_type' => $fileInfo['originalMime'],
            'file_size' => $fileInfo['originalSize'],
            'title' => (!empty($fileInfo['title']) ? $fileInfo['title'] : ''),
            'path' => $newPath
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
        return $this->app['config']->get('attach.root') . DIRECTORY_SEPARATOR . $this->path;
//        $fileInfo = [
//            'originalName' => $this->file_name,
//            'filename' => $this->path
//        ];
//        $path = $this->attachCreateFilename($fileInfo);
//        //var_dump($path);
//        //var_dump($this->attach_options);
//        return $path;
    }

    function attachStoreTmpFile($file)
    {
        $ret = $this->attachCreateFileInfo($file);
        $name = str_replace(".", "_", uniqid('tmp', true));
        $target = $this->app['config']->get("attach.root") . DIRECTORY_SEPARATOR . "tmp";
        if (!file_exists($target))
        {
            $this->app['files']->makeDirectory($target, 0755, true, true);
        }
        $file->move($target, $name);
        $ret['originalPath'] = $target.DIRECTORY_SEPARATOR.$name;
        $ret['fileObj'] = new File($ret['originalPath']);
        return $ret;

    }

    public function  attachCreateFilename($fileInfo, $args = [])
    {
        $parts = [];
        $parts[] = $args['path'];
//        $path = !empty($args['path']) ? $args['path'] : '%l1/%l2';
//        $md5 = md5($fileInfo['originalName']);
//        $path = str_replace('%l1', substr($md5,0,2), $path);
//        $path = str_replace('%l2', substr($md5,2,2), $path);
//        if(!empty($args['instance_id']))
//        {
//            $path = str_replace('%i3', str_pad($args['instance_id'] % 1000, 3, '0', STR_PAD_LEFT), $path);
//            $path = str_replace('%id', $args['instance_id'], $path);
//        }
//        $parts[] = $path;
        $parts[] = str_replace(".", "_", uniqid(!empty($args['prefix']) ? $args['prefix'] : 'img', true)) . "." . $fileInfo['originalExt'];

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

    function attachResize($w, $h, $crop = false)
    {
        $filename = $this->attachGetPath();
        $resized_filename = str_replace($this->app['config']->get('attach.root'), $this->app['config']->get('attach.resized_path'), dirname($filename)) . DIRECTORY_SEPARATOR . $w . "z" . $h . "_" . ($crop ? 'crop' : 'full') . '_' . basename($filename);
        if (!file_exists($resized_filename))
        {
            \Log :: info('resizing', ['browsify' => true]);
            $img = \Image :: make($filename);
            if ($crop)
            {
                $img->fit($w, $h);
            }
            else
            {
                $img->resize($w, $h, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            if (!file_exists(dirname($resized_filename)))
            {
                $this->app['files']->makeDirectory(dirname($resized_filename), 0755, true, true);
            }
            $img->save($resized_filename);
        }
        return $resized_filename;
    }

    function getResizedPath($w, $h, $crop = false)
    {
        return $this->attachResize($w, $h, $crop);
    }

    function getResizedUrl($w, $h, $crop = false)
    {
        $path = $this->attachResize($w, $h, $crop);
        return str_replace($this->app['config']->get('attach.resized_path'), $this->app['config']->get('attach.resized_url'), $path);
    }


    public function getDownloadLinkAttribute()
    {
        $symlink = $this->app['config']->get('attach.symlink');
        if (!empty($symlink))
        {
            return '/'.str_replace($this->app['config']->get('attach.root'), $symlink,$this->attachGetPath());
        }
        return $this->app['url']->route('download_attach',array('id' => $this->id, 'filename'=>urlencode($this->file_name), 'model' => $this->classViewName));
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