<?php namespace Skvn\Crud\Traits;






/**
 * Class InlineImgTrait
 * Provides process inline images functionality
 * @package Skvn\Crud
 * @author Vitaly Nikolenko <vit@webstandart.ru>
 */
trait InlineImgTrait {


    /**
     * @var array columns that should be processed
     */
    protected $processCols = [];
    /**
     * @var int Max image width
     */
    protected $maxWidth = 2000;


    /**
     * @param $cols Set columns that should be processed
     */
    public function setInlineImgCols($cols)
    {

        if (!is_array($cols))
        {
            $cols = [$cols];
        }
        $this->processCols = $cols;
    }


    /**
     * Laravel model boot
     */
    public static function boot()
    {


        static::saving(function($instance) {


            foreach ($instance->processCols as $attr)
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

            \Log::info($matches);
            \Log::info($text);


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
                    $img = \Image::make(base64_decode($base_64));
                    $originalWidth = $img->width();
                    if (strpos($width,'%') !== false)
                    {

                        $newWidth = $originalWidth/100*intval(trim(str_replace('%','',$width)));


                    } else {
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
                    $publicPath = '/images/'.$this->table.'/'.$this->id.'/'.uniqid('img').'.'.$ext;
                    $path = public_path().$publicPath;
                    \File::makeDirectory(dirname($path), 0755, true, true);
                    $img->save($path);

                    $text = str_replace($src,$publicPath, $text);


                }
            }
        }



        return $text;
    }



} 