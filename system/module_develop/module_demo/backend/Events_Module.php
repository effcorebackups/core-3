<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore\modules\demo;

use DateTime;
use DateTimeZone;
use effcore\Instance;
use effcore\Module;
use effcore\Storage;

abstract class Events_Module {

    static function on_install($event) {
        $module = Module::get('demo');
        $module->install();
        if (count(Storage::get('sql')->errors) === 0) {
            for ($i = 1; $i <= 500; $i++) {
                $c_base_date = new DateTime('2030-01-01 00:08:20', new DateTimeZone('UTC'));
                $c_id = $i;
                $c_nickname = 'user_'.str_pad((string)$i, 3, '0', STR_PAD_LEFT);
                $c_email = $c_nickname.'@example.com';
                $c_created = $c_base_date->modify('-'.$i.' second')->format('Y-m-d H:i:s');
                $c_is_even = $i % 2 ? 0 : 1;
                (new Instance('demo_data', [
                    'id'       => $c_id,
                    'nickname' => $c_nickname,
                    'created'  => $c_created,
                    'is_even'  => $c_is_even
                ]))->insert();
                (new Instance('demo_join', [
                    'id_data' => $c_id,
                    'email'   => $c_email
                ]))->insert();
            }
        }
    }

    static function on_uninstall($event) {
        $module = Module::get('demo');
        $module->uninstall();
    }

    static function on_enable($event) {
        if (Module::is_installed('demo')) {
            $module = Module::get('demo');
            $module->enable();
        }
    }

    static function on_disable($event) {
        $module = Module::get('demo');
        $module->disable();
    }

    static function on_start($event) {
    }

}
