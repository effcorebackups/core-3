<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore\modules\user {
          use \effcore\core;
          use \effcore\instance;
          use \effcore\module;
          use \effcore\request;
          use \effcore\session;
          use \effcore\storage;
          abstract class events_module {

  static function on_install($event) {
    $module = module::get('user');
    $module->install();
    if (count(storage::get('sql')->errors) === 0) {
      $admin = new instance('user', ['nickname' => 'Admin']);
      if ($admin->select()) {
        $admin->password_hash = core::password_get_hash(request::value_get('password'));
        $admin->email         =                         request::value_get('email'   );
        $admin->timezone      =                         request::value_get('timezone');
        $admin->update();
      }
    }
  }

  static function on_enable($event) {
    $module = module::get('user');
    $module->enable();
  }

  static function on_cron_run($event) {
    session::cleaning();
  }

}}