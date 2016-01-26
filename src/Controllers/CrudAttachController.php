<?php namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use Skvn\Crud\CrudFile;
use Symfony\Component\HttpFoundation\File\File;

class CrudAttachController extends Controller {


    public function download($id)
    {

        $attachObj = CrudFile::findOrFail($id);
        if (file_exists($attachObj->path))
        {
            $f = new File($attachObj->path);
            header("Content-Type: ".$f->getMimeType());
            header("Content-Length: " . filesize($attachObj->path));
            readfile($attachObj->path);
            exit;
        }
        
        //return \Response::download($attachObj->path);


    }

}
