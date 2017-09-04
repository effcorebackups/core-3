<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore\modules\storage {
          use \effectivecore\timers_factory as timers;
          use \effectivecore\console_factory as console;
          use \effectivecore\translations_factory as translations;
          abstract class events_storage {

  static function on_storage_init_before(&$instance) {
    timers::tap('init_pdo');
  }

  static function on_storage_init_after(&$instance) {
    timers::tap('init_pdo');
    console::add_log(
      'storage', 'init', translations::get('The storage %%_name was initialized on first request.', ['name' => $instance->id]), 'ok', timers::get_period('init_pdo', 0, 1)
    );
  }

  static function on_query_before(&$instance, &$query) {
    timers::tap('query_'.md5($query));
  }

  static function on_query_after(&$instance, &$query, &$result, &$errors) {
    timers::tap('query_'.md5($query));
    console::add_log(
      'storage', 'query', $query, $errors[0] == '00000' ? 'ok' : 'error', timers::get_period('query_'.md5($query), -1, -2)
    );
  }

}}