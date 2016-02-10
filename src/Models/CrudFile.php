<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Skvn\Crud\Handlers\AttachmentHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CrudFile extends Model
{


    protected $table = 'crud_file';
    protected $guarded = ['id'];

    public function setCreatedAtAttribute($value)
    {

        if (is_object($value)) {
            $value = $value->timestamp;
        } else {
            $value = strtotime($value);
        }
        $this->attributes['created_at'] = $value;
    }

    public function setUpdatedAtAttribute($value)
    {

        if (is_object($value)) {
            $value = $value->timestamp;
        } else {
            $value = strtotime($value);
        }
        $this->attributes['updated_at'] = $value;
    }

    public function getDownloadLinkAttribute()
    {
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

    public static function boot()
    {

        parent::boot();

        static::deleting(function($instance) {
            if (file_exists($instance->path))
            {
                unlink($instance->path);
            }
        });

    }

    public static function  createFromUpload(UploadedFile $file)
    {

        $file_data = AttachmentHandler::generateFdata($file);
        $dest = AttachmentHandler::generateSaveFilename($file_data);
        \File::makeDirectory(dirname($dest), 0755, true, true);
        if (file_exists($dest))
        {
            unlink($dest);
        }

        $file->move(dirname($dest), basename($dest));

        $instance = new CrudFile();
        $instance->fill(['file_name' => $file_data['originalName'],
            'mime_type' => $file_data['originalMime'],
            'file_size' => $file_data['originalSize'],
            'title' => (!empty($file_data['title'])?$file_data['title']:''),
            'path' => $dest
        ]);
        $instance->save();
        return $instance;

    }


}