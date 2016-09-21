<?php

namespace Skvn\Crud\Traits;

use Illuminate\Support\Str;
use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Exceptions\UniqueException;

trait ModelSlugTrait
{
    //    protected  static  $slugColumn = 'slug';

    public static function bootModelSlugTrait()
    {
        static :: saving(function ($instance) {
            //            if ($instance->eventsDisabled)
//            {
//                return true;
//            }
            return $instance->processSlug();
        });
    }

    public static function slugColumn()
    {
        return defined('static::SLUG') ? static :: SLUG : 'slug';
    }

    public function getFrontUrlAttribute()
    {
        if (! defined('static::SLUG_URL')) {
            throw new ConfigException('Url pattern not defined');
        }
        if ($this->getAttribute(static :: slugColumn())) {
            return sprintf(static :: SLUG_URL, $this->getAttribute(static :: slugColumn()));
        } else {
            return sprintf(static :: SLUG_URL, $this->getKey());
        }
    }

    protected function processSlug()
    {
        $column = static :: slugColumn();
        if (defined('static::SLUG_IMMUTABLE') && $this->getOriginal($column)) {
            $this->setAttribute($column, $this->getOriginal($column));

            return;
        }
        try {
            $this->setAttribute($column, $this->generateSlug($this->getAttribute($column)));
        } catch (UniqueException $e) {
            var_dump($e->getMessage());
            //var_dump($e->getTraceAsString());
            $this->addError($column, $e->getMessage());

            return false;
        }
    }

    protected function generateSlug($slug)
    {
        if (empty($slug)) {
            $slug = defined('static::SLUG_SOURCE') ? $this->getAttribute(static :: SLUG_SOURCE) : $this->getTitle();
            $slug = $this->translitRussian($slug);
            $slug = Str :: slug($slug);
        }
        $slug = $this->generateUniqueSlug($slug);

        return $slug;
    }

    private function generateUniqueSlug($slug)
    {
        //        $column = static :: slugColumn();
        $valid = $this->validateSlug($slug);
        if ($valid === -99) {
            if (defined('static::SLUG_FORCE_TRANSLIT')) {
                $slug = $this->translitRussian($slug);
                $slug = Str :: slug($slug);
            } else {
                throw new UniqueException('Slug('.$slug.') in model '.$this->classShortName.'#'.$this->getKey().' not in URI format. Acceptable format is [a-zA-Z0-9_-]');
            }
        } elseif ($valid < 0) {
            if (defined('static::SLUG_GENERATE_NEXT')) {
                return $slug.'.'.(abs($valid) + 1);
            }
            if (defined('static::SLUG_GENERATE_ID') && $this->exists) {
                return $slug.'.'.$this->getKey();
            }
            throw new UniqueException('Slug column('.$slug.') for model '.$this->classShortName.'#'.$this->getKey().' is not unique');
        }
//        $column = static :: slugColumn();
//        if (! preg_match('#^[a-zA-Z0-9_-]+$#', $slug)) {
//            if (defined('static::SLUG_FORCE_TRANSLIT')) {
//                $slug = $this->translitRussian($slug);
//                $slug = Str :: slug($slug);
//            } else {
//                throw new UniqueException($column.'('.$slug.') in model '.$this->classShortName.'#'.$this->getKey().' not in URI format. Acceptable format is [a-zA-Z0-9_-]');
//            }
//        }

//        $id = $this->getKey() ? $this->getKey() : 0;
//        $exists = $this->app['db']->table($this->table)->where($column, 'like', $slug)->where('id', '<>', $id)->get();
//        if (count($exists) > 0) {
//            if (defined('static::SLUG_GENERATE_NEXT')) {
//                return $slug.'.'.(count($exists) + 1);
//            }
//            if (defined('static::SLUG_GENERATE_ID') && $this->exists) {
//                return $slug.'.'.$this->getKey();
//            }
//            throw new UniqueException('Slug column('.$slug.') for model '.$this->classShortName.'#'.$this->getKey().' is not unique');
//        }

        return $slug;
    }

    public function validateSlug($slug)
    {
        if (! preg_match('#^[a-zA-Z0-9_-]+$#', $slug)) {
            return -99;
        }
        $column = static :: slugColumn();
        $exists = $this->app['db']->table($this->table)->where($column, 'like', $slug)->where('id', '<>', $this->getKey())->get();
        if (count($exists) > 0) {
            return count($exists);
        }

        return 0;
    }

    private function translitRussian($input, $url_escape = false, $tolower = false)
    {
        $arrRus = ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м',
            'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ь',
            'ы', 'ъ', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М',
            'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь',
            'Ы', 'Ъ', 'Э', 'Ю', 'Я', ];
        $arrEng = ['a', 'b', 'v', 'g', 'd', 'e', 'jo', 'zh', 'z', 'i', 'y', 'k', 'l', 'm',
            'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'c', 'ch', 'sh', 'sch', '',
            'y', '', 'e', 'yu', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'JO', 'ZH', 'Z', 'I', 'Y', 'K', 'L', 'M',
            'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'KH', 'C', 'CH', 'SH', 'SCH', '',
            'Y', '', 'E', 'YU', 'YA', ];

        $input = str_replace(' ', '-', $input);
        if ($tolower) {
            $input = mb_strtolower($input, 'UTF-8');
        }
        $result = str_replace($arrRus, $arrEng, $input);
        $result = preg_replace('#[^_-a-zA-Z0-9]#i', '', $result);
        if ($url_escape) {
            $result = str_replace([' ', '/', '\\'], '_', $result);
            $result = urlencode($result);
        }

        return $result;
    }

    public static function findBySlug($slug)
    {
        $class = get_called_class();
        $column = $class :: slugColumn();

        return $class :: where($column, $slug)->firstOrFail();
    }

    public function slugTransliterate($args = [])
    {
        if (! empty($args['source']) && ! empty($args[$args['source']])) {
            $slug = $this->translitRussian($args[$args['source']]);
            $slug = Str :: slug($slug);

            return $slug;
        }
    }
}
