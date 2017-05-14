<?php

namespace Skvn\Crud\Helper;

class CmsHelper
{
    protected $acls = [];
    public $user;
    protected $menus = [];
    protected $app;

    public function __construct()
    {
        $this->app = app();
    }

    public function getUser()
    {
        if (!$this->user) {
            $this->user = $this->app['auth']->user();
            $this->acls = $this->user->getAcls();

        }
        return $this->user;
    }

    public function getAcls()
    {
        if (!count($this->acls)) {
            $this->getUser();
        }
        return $this->acls;
    }
    public function getAdminMenu()
    {

        $conf = $this->app['config']->get('admin_menu');
        $menu = [];
        foreach ($conf as $index => $parent) {
            if (empty($parent['acl']) || $this->checkAcl($parent['acl'])) {
                $item = $parent;
                $item['kids'] = null;

                if (! empty($parent['route'])) {
                    if ($this->app['request']->url() == route($parent['route']['name'], $parent['route']['args'])) {
                        $item['active'] = true;
                    } else {
                        $item['active'] = false;
                    }
                } else {
                    $item['active'] = false;
                }

                if (! empty($parent['kids'])) {
                    foreach ($parent['kids'] as $kindex => $kid) {
                        if (empty($kid['acl']) || $this->checkAcl($kid['acl'])) {
                            $item['kids'][$kindex] = $kid;

                            if (! isset($kid['route']['args'])) {
                                $kid['route']['args'] = null;
                            }
                            if ($this->app['request']->url() == rtrim(route($kid['route']['name'], $kid['route']['args']), '?')) {
                                $item['active'] = true;
                                $item['kids'][$kindex]['active'] = true;
                            } else {
                                $item['kids'][$kindex]['active'] = false;
                            }
                        }
                    }
                }

                $menu[$index] = $item;
            }
        }


        return $menu;
    }



    public function checkAcl($acl, $access = '')
    {

        $this->getAcls();
        if (empty($acl)) {
            return true;
        }
        if (array_key_exists('all', $this->acls)) {
            return true;
        }
        $list = explode(',', $acl);
        foreach ($list as $check) {
            if (array_key_exists($check, $this->acls)) {
                if (empty($access)) {
                    return true;
                }
                if ($this->acls[$check] == '*') {
                    return true;
                }
                if (strpos($this->acls[$check], $access) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function setMenu($name, $data)
    {
        $this->menus[$name] = $data;
    }

    public function getMenu($name)
    {
        if (! empty($this->menus[$name])) {
            return $this->menus[$name];
        }
    }

    public function setBreadCrumbs($data)
    {
        $this->setMenu('__bc', $data);
    }

    public function getBreadCrumbs()
    {
        $start = ['title' => 'Главная', 'link' => '/'];
        $bc = $this->getMenu('__bc');
        if (count($bc)) {
            array_unshift($bc, $start);

            return $bc;
        } else {
            $start['link'] = null;

            return [$start];
        }
    }

    public function isBot()
    {
        $bot_agents = [
            'msnbot',
            'google',
            'ia_archiver',
            'yahoo',
            'webalta',
            'FlickySearchBot',
            'Yanga',
            'rambler',
            'mail.ru',
            'yandex',

        ];
        if (! empty($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            foreach ($bot_agents as $bot) {
                if (stripos($user_agent, $bot) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

//

    public function getMonths()
    {
        $months['1'] = 'января';
        $months['2'] = 'февраля';
        $months['3'] = 'марта';
        $months['4'] = 'апреля';
        $months['5'] = 'мая';
        $months['6'] = 'июня';
        $months['7'] = 'июля';
        $months['8'] = 'августа';
        $months['9'] = 'сентября';
        $months['10'] = 'октября';
        $months['11'] = 'ноября';
        $months['12'] = 'декабря';

        return $months;
    }

    public function smartDate($time_stamp = '', $sayMonth = 1, $sayToday = 0, $dateFormat = 'dd mm', $smart_year = 0)
    {
        $months = $this->getMonths();
        if ($dateFormat == 'simple') {
            $sayMonth = true;
            $sayToday = false;
            $dateFormat = 'dd mm';
        }

        if ($dateFormat == 'list') {
            $sayMonth = false;
            $sayToday = false;
            $dateFormat = 'dd.mm.yyyy';
            $smart_year = 1;
        }

        if ($dateFormat == 'full') {
            $sayMonth = true;
            $sayToday = false;
            $dateFormat = 'dd mm yyyy';
        }

        //$dateFormat='dd,mm,yyyy H:M'

        if (! empty($time_stamp)) {
            $mysqldate = date('Y-m-d H:i:s', $time_stamp);
        } else {
            $mysqldate = date('Y-m-d H:i:s');
        }

        if (! empty($mysqldate)) {
            $datearr = explode(' ', $mysqldate);
            $date = $datearr[0];
            if (isset($datearr[1])) {
                $time = $datearr[1];
            } else {
                $time = '00:00:00';
            }

            $date_arr = explode('-', $date);
            $time_arr = explode(':', $time);

            $year = $date_arr[0];
            $month = $date_arr[1];
            $day = $date_arr[2];

            $hour = $time_arr[0];
            $min = $time_arr[1];
            $second = $time_arr[2];



            if ($sayToday) {
                if (date('Ymd') == $year.$month.$day) {
                    $day = 'сегодня';
                    $month = '';
                    $year = '';
                } elseif (date('Ymd', strtotime('yesterday')) == $year.$month.$day) {
                    $day = 'вчера';
                    $month = '';
                    $year = '';
                } elseif (date('Ymd', strtotime('tomorrow')) == $year.$month.$day) {
                    $day = 'завтра';
                    $month = '';
                    $year = '';
                }
            }

            if ($month != '') {
                //if (!substr_count($dateFormat, 'dd'))
                // {
                $day = intval($day);
                // }
            }



            if ($sayMonth && $month != '') {
                $month = (int) $month;
                if (isset($months[$month])) {
                    $month = $months[intval($month)];
                }
            }

            if ($month != '') {
                if (! substr_count($dateFormat, 'mm')) {
                    $month = intval($month);
                }
            }

            $numDigY = substr_count($dateFormat, 'y');

            if ($smart_year) {
                if ($year == date('Y')) {
                    $year = '';
                    $returnDate = preg_replace('/\.(y)+/', $year, $dateFormat);
                    $returnDate = preg_replace('/(y)+/', $year, $returnDate);
                } else {
                    $returnDate = preg_replace('/(y)+/', $year, $dateFormat);
                }
            } else {
                $year = substr($year, -$numDigY, 4);
                $returnDate = preg_replace('/(y)+/', $year, $dateFormat);
            }




            $returnDate = preg_replace('/dd|d/', $day, $returnDate);
            $returnDate = preg_replace('/mm|m/', $month, $returnDate);

            $returnDate = str_replace('H', $hour, $returnDate);
            $returnDate = str_replace('M', $min, $returnDate);

            //strip stuff
            $returnDate = str_replace(' ,', ',', $returnDate);
            $returnDate = str_replace(' .', '.', $returnDate);
            $returnDate = str_replace(' /', '/', $returnDate);



            $returnDate = str_replace(' ,', ',', $returnDate);
            $returnDate = str_replace(' .', '.', $returnDate);
            $returnDate = str_replace(' /', '/', $returnDate);

            return $returnDate;
        }

        return false;
    }

    public function getLocale()
    {
        return $this->app->getLocale();
    }

    public function getVendorJs()
    {
        $config = json_decode(file_get_contents(__DIR__.'/../../gulp-config.json'), true)['paths']['vendor_js_src'];
        array_walk($config, function (&$item) {
            $item = str_replace('resources', '', $item);
        });

        return $config;
        //resources/bower_components/
    }
}
