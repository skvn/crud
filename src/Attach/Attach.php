<?php

namespace Skvn\Attach;

use \Illuminate\Database\Eloquent\Model;

class Attach extends Model
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
        return \URL::route('download_attach',array('id' => $this->id, 'filename'=>basename($this->path)));
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


}