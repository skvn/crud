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

            $fp = fopen($attachObj->path,"rb");
            if (strpos($attachObj->mime_type,'image') !== false )
            {
                header("Content-Type: ".$attachObj->mime_type);
                fpassthru($fp);
                fclose($fp);
                exit;

            } else {

                if ($fp) {
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Pragma: no-cache"); //keeps ie happy
                    header("Content-Disposition: attachment; filename= " . $attachObj->file_name);
                    header("Content-Type: " . $attachObj->mime_type);
                    header("Content-Length: " . (string)filesize($attachObj->path));
                    header('Content-Transfer-Encoding: binary');

                    ob_end_clean();//required here or large files will not work
                    @fpassthru($fp);//works fine now
                    fclose($fp);
                }
            }

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
