<?php

namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use Skvn\Crud\Models\CrudFile;
use Skvn\Crud\Models\CrudModel;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CrudAttachController extends Controller
{
    public function download($model, $id)
    {
        if ($model == 'CrudFile') {
            $attachObj = CrudFile::findOrFail($id);
        } else {
            $attachObj = CrudModel :: createInstance($model, null, $id);
        }
        if (file_exists($attachObj->attachGetPath())) {
            $fp = fopen($attachObj->attachGetPath(), 'rb');
            if (strpos($attachObj->mime_type, 'image') !== false) {
                header('Content-Type: '.$attachObj->mime_type);
                while (!feof($fp)) {
                    echo fread($fp, 1024);
                }
            } else {
                if ($fp) {
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache'); //keeps ie happy
                    header('Content-Disposition: attachment; filename= '.$attachObj->file_name);
                    header('Content-Type: '.$attachObj->mime_type);
                    header('Content-Length: '.(string) filesize($attachObj->path));
                    header('Content-Transfer-Encoding: binary');

                    while (!feof($fp)) {
                        echo fread($fp, 1024);
                    }
                }
            }

            fclose($fp);
        }

        exit;
        //return \Response::download($attachObj->path);
    }

    public function upload()
    {
        if (\Request::hasFile('file')) {
            $file = \Request::file('file');
            if ($file instanceof UploadedFile) {
                $crud_file = CrudFile::createFromUpload($file);
                $ret = [
                    'id'  => $crud_file->id,
                    'url' => $crud_file->getDownloadLinkAttribute(),
                ];
                if (strpos($crud_file->mime_type, 'image') !== false) {
                    $dimensions = getimagesize($crud_file->path);
                    $ret['width'] = $dimensions[0];
                    $ret['height'] = $dimensions[1];
                }

                return $ret;
            }
        }
    }
}
