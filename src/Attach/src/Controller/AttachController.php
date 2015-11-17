<?php namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use LaravelAttach\Attach;
use Symfony\Component\HttpFoundation\File\File;

class AttachController extends Controller {


    public function download($id)
    {

        $attachObj = Attach::findOrFail($id);
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
