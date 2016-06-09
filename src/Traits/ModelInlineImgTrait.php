<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Form\Field;

/**
 * Class ModelInlineImgTrait
 * Provides process inline images functionality
 * @package Skvn\Crud
 * @author Vitaly Nikolenko <vit@webstandart.ru>
 */
trait ModelInlineImgTrait {


    /**
     * @var array columns that should be processed
     */
    protected $inlimgCols = [];
    /**
     * @var int Max image width
     */
    protected $maxWidth = 2000;



    protected function appendInlineImgConfig()
    {
        $cols = [];
        if (!empty($this->config['fields']))
        {
            foreach ($this->config['fields'] as $name => $field)
            {
                if (!empty($field['type']) && in_array($field['type'], [Field :: TEXTAREA]) && !empty($field['editor']))
                {
                    $cols[] = $name;
                }
            }
        }
        $this->inlimgCols = $cols;
    }


    /**
     * Laravel model boot
     */
    public static function bootModelInlineImgTrait()
    {
        static::registerPostconstruct(function($instance){
            $instance->appendInlineImgConfig();
        });
        static::saving(function($instance) {
            foreach ($instance->inlimgCols as $attr)
            {
                $instance->setAttribute($attr, $instance->processInlineImgs($instance->getAttribute($attr)));
            }
        });
    }


    /**
     * Proces text for inline images
     * @param $text
     * @return mixed
     */
    public  function processInlineImgs($text)
    {
        if (preg_match_all('#(<img\s(?>(?!src=)[^>])*?src=")(data:image/(gif|png|jpeg);base64,([\w=+/]++))("[^>].*>)#siUm', $text, $matches, PREG_SET_ORDER))
        {

            foreach ($matches as $m)
            {
                if (!empty($m[4]))
                {
                    if (preg_match("#width:(.*);#siU",$m[0], $wm))
                    {

                        $width = trim($wm[1]);
                    }
                    $src = $m[2];
                    $base_64 = $m[4];
                    $img = \Image :: make(base64_decode($base_64));
                    $originalWidth = $img->width();
                    if (strpos($width,'%') !== false)
                    {
                        $newWidth = $originalWidth/100*intval(trim(str_replace('%','',$width)));
                    }
                    else
                    {
                        $newWidth = (int)trim(str_replace('px','',$width));
                    }
                    $resizeWidth = $this->maxWidth;
                    if ($newWidth>$this->maxWidth)
                    {
                        $resizeWidth = $newWidth;
                    }
                    $img->resize($resizeWidth, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $ext = $m[3];
                    if ($ext == 'jpeg')
                    {
                        $ext = 'jpg';
                    }
                    $filename = $this->generateInlineImgFilename($ext);
                    $this->app['files']->makeDirectory(dirname($this->getInlineImgPath($filename)), 0755, true, true);
                    $img->save($this->getInlineImgPath($filename));

                    $text = str_replace($src, $this->getInlineImgUrl($filename), $text);
                }
            }
        }
        return $text;
    }

    protected function getInlineImgFilename($filename)
    {
        return $this->getFilesConfig($filename, "inline_path") . DIRECTORY_SEPARATOR . $filename;
        //return ($this->getFilesConfig($filename, "inline_path") ?: ('images/' . $this->table . '/' . $this->id)) . DIRECTORY_SEPARATOR . $filename;
    }

    protected function getInlineImgUrl($filename)
    {
        return $this->getFilesConfig($filename, "inline_url") . "/" .  $this->getInlineImgFilename($filename);
//        if ($url = $this->getFilesConfig($filename, "inline_url"))
//        {
//        }
//        return '/' . $this->getInlineImgFilename($filename);
    }

    protected function getInlineImgPath($filename)
    {
        return $this->getFilesConfig($filename, "inline_root") . DIRECTORY_SEPARATOR . $this->getInlineImgFilename($filename);
//        if ($root = )
//        {
//            return $root . DIRECTORY_SEPARATOR . $this->getInlineImgFilename($filename);
//        }
//        return public_path($this->getInlineImgFilename($filename));
    }

    protected function generateInlineImgFilename($ext)
    {
        return str_replace(".", "-", uniqid($this->getFilesConfig('inline', 'prefix') ?: 'img', true)).'.'.$ext;
    }


} 