<?php namespace Skvn\Crud\Models;

use \Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Skvn\Crud\Traits\ModelAttachedTrait;
use Illuminate\Container\Container;

class CrudFile extends Model
{
    use ModelAttachedTrait;


    protected $table = 'crud_file';
    protected $guarded = ['id'];
    protected $app;

    function __construct(array $attributes = [])
    {
        parent :: __construct($attributes);
        $this->app = Container :: getInstance();
    }

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




    public static function  createFromUpload(UploadedFile $file)
    {
        $instance = new CrudFile();
        $file_data = $instance->attachCreateFileInfo();
        $instance->attachStoreFile($file_data);


//        $dest = AttachmentHandler::generateSaveFilename($file_data);
//        \File::makeDirectory(dirname($dest), 0755, true, true);
//        if (file_exists($dest))
//        {
//            unlink($dest);
//        }
//
//        $file->move(dirname($dest), basename($dest));
//
//        $instance->fill(['file_name' => $file_data['originalName'],
//            'mime_type' => $file_data['originalMime'],
//            'file_size' => $file_data['originalSize'],
//            'title' => (!empty($file_data['title'])?$file_data['title']:''),
//            'path' => $dest
//        ]);
//        $instance->save();
        return $instance;

    }


}