<?php namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Skvn\Crud\Models\CrudFile;


class CrudAttachController extends Controller {


    public function download($id)
    {

        $attachObj = CrudFile::findOrFail($id);
        if (file_exists($attachObj->path))
        {
            //$fp = fopen($attachObj->path, 'rb');
            //$f = new File($attachObj->path);

            //readfile($attachObj->path);

            header("Content-Type: ".$attachObj->mime_type);
            //header("Content-Length: " . filesize($attachObj->path));
            $fp = fopen($attachObj->path,"rb");
            fpassthru($fp);
            fclose($fp);
            exit;
        }
        
        //return \Response::download($attachObj->path);

    }

    public  function upload()
    {
        if (\Request::hasFile('file')) {
            $file = \Request::file('file');
            if ($file instanceof UploadedFile)
            {
                $crud_file = CrudFile::createFromUpload($file);
                $ret =  [
                    'id'=>$crud_file->id,
                    'url' => $crud_file->getDownloadLinkAttribute(),
                ];
                if (strpos($crud_file->mime_type,'image') !== false)
                {
                    $dimensions = getimagesize($crud_file->path);
                    $ret['width'] = $dimensions[0];
                    $ret['height'] = $dimensions[1];
                }
                return $ret;
            }
        }
    }

}
