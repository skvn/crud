<?php namespace Skvn\Crud\Traits;

use Illuminate\Support\Str;

trait ModelSlugTrait  {

    protected  static  $slugColumn = 'slug';

//    public static function boot()
//    {
//
//        //parent::boot();
//        static::saving(function($instance)
//        {
//
//            if (!$instance->getAttribute($instance->slugColumn))
//            {
//                $instance->setAttribute($instance->slugColumn, $instance->generateSlug());
//            } else
//            {
//                $instance->setAttribute($instance->slugColumn, $instance->validateSlugUnique(
//                    Str::slug(
//                        $instance->getAttribute(
//                            $instance->slugColumn
//                        )
//                    )
//                )
//                );
//            }
//        });
//    }

    static function bootModelSlugTrait()
    {
        static :: saving(function($instance){
            $instance->processSlug();
        });
    }

    protected function processSlug()
    {
        if (!$this->getAttribute(static::$slugColumn))
            {
                $this->setAttribute(static::$slugColumn, $this->generateSlug());
            } else
            {
                $this->setAttribute(static::$slugColumn, $this->validateSlugUnique(
                    Str::slug($this->getAttribute(static::$slugColumn))
                )
                );
            }

        //parent::onBeforeSave();
    }


    protected  function generateSlug()
    {
        return $this->validateSlugUnique(
                        Str::slug(
                            $this->translitRussian($this->getTitle())
                        )
        );
    }

    private function validateSlugUnique($slug)
    {
        $id = $this->id?$this->id:0;
        $count =  count(\DB::table($this->table)->where(static::$slugColumn, $slug)->where('id', '<>',$id)->get());
        if ($count>0)
        {
            $slug .= ($count+1);
        }

        return $slug;
    }


    private function  translitRussian($input, $url_escape = false, $tolower=false)
    {
        $arrRus = array('а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м',
            'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь',
            'ы', 'ъ', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', '�?', 'Й', 'К', 'Л', 'М',
            'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь',
            'Ы', 'Ъ', 'Э', 'Ю', 'Я');
        $arrEng = array('a', 'b', 'v', 'g', 'd', 'e', 'jo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm',
            'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'c', 'ch', 'sh', 'sch', '',
            'y', '', 'e', 'yu', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'JO', 'ZH', 'Z', 'I', 'Y', 'K', 'L', 'M',
            'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'KH', 'C', 'CH', 'SH', 'SCH', '',
            'Y', '', 'E', 'YU', 'YA');

        $input = str_replace(' ', '-', $input);
        if ($tolower)
        {
            $input = mb_strtolower($input,'UTF-8');
        }
        $result = str_replace($arrRus, $arrEng, $input);
        $result = preg_replace("#[^_-a-zA-Z0-9]#i", '', $result);
        if ($url_escape)
        {
            $result = str_replace(array(' ', '/', '\\'), '_', $result);
            $result = urlencode($result);
        }

        return $result;
    }


    static function findBySlug($slug)
    {
        return self::where(static::$slugColumn,'=',$slug)->first();
    }


}
