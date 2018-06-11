<?php

  ##################################################################
  ### Copyright © 2017—2018 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          abstract class locale {

  static protected $cache_settings;
  static protected $cache_countries;

  static function init() {
    static::$cache_settings = storage::get('files')->select('settings/locales');
    foreach (storage::get('files')->select('countries') as $c_module_id => $c_countries) {
      foreach ($c_countries as $c_row_id => $c_country) {
        if (isset(static::$cache_countries[$c_country->code])) console::log_about_duplicate_add('country', $c_country->code);
        static::$cache_countries[$c_country->code] = $c_country;
        static::$cache_countries[$c_country->code]->module_id = $c_module_id;
      }
    }
  }

  static function countries_get() {
    if   (!static::$cache_countries) static::init();
    return static::$cache_countries;
  }

  static function settings_get() {
    if   (!static::$cache_settings) static::init();
    return static::$cache_settings;
  }

  ###############
  ### formats ###
  ###############

  static function format_time($time)                      {return \DateTime::createFromFormat('H:i:s',       $time,     new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone( static::settings_get()->timezone ))->format( static::settings_get()->format_time );}
  static function format_date($date)                      {return \DateTime::createFromFormat('Y-m-d',       $date,     new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone( static::settings_get()->timezone ))->format( static::settings_get()->format_date );}
  static function format_datetime($datetime)              {return \DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new \DateTimeZone('UTC'))->setTimezone(new \DateTimeZone( static::settings_get()->timezone ))->format( static::settings_get()->format_datetime );}
  static function format_timestamp($timestamp)            {return \DateTime::createFromFormat('U',           $timestamp                         )->setTimezone(new \DateTimeZone( static::settings_get()->timezone ))->format( static::settings_get()->format_datetime );}
  static function format_persent($number, $precision = 2) {return static::format_number(floatval($number), $precision).'%';}
  static function format_msecond($number, $precision = 6) {return static::format_number(floatval($number), $precision);}
  static function format_version($number)                 {return static::format_number(floatval($number), 2);}

  static function format_number($number, $precision = 0, $dec_point = null, $thousands = null, $no_zeros = true) {
    $dec_point = is_null($dec_point) ? static::settings_get()->decimal_point       : $dec_point;
    $thousands = is_null($thousands) ? static::settings_get()->thousands_separator : $thousands;
    return core::format_number($number, $precision, $dec_point, $thousands, $no_zeros);
  }

  static function format_human_bytes($bytes, $decimals = 2) {
    return core::bytes_to_human($bytes, $decimals, static::settings_get()->decimal_point);
  }

}}