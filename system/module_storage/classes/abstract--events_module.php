<?php

namespace effectivecore\modules\storage {
          use \effectivecore\settings;
          use \effectivecore\factory;
          abstract class events_module extends \effectivecore\events_module {

  static function on_init() {
    storage::init();

    $is_init = db::init(
      settings::$data['db']['storage']->prod->driver,
      settings::$data['db']['storage']->prod->host,
      settings::$data['db']['storage']->prod->database_name,
      settings::$data['db']['storage']->prod->username,
      settings::$data['db']['storage']->prod->password,
      settings::$data['db']['storage']->prod->table_prefix
    );
    if (!$is_init) {
      factory::send_header_and_exit('access_denided',
        'Database is unavailable!'
      );
    }
  }

}}