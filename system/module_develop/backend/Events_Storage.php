<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore\modules\develop;

use effcore\Console;
use effcore\Core;
use effcore\Security;
use effcore\Timer;
use PDO;

abstract class Events_Storage {

    static function on_init_before($event, $storage) {
        Timer::tap('storage init');
    }

    static function on_init_after($event, $storage) {
        Timer::tap('storage init');
        Console::log_insert('storage', 'init.', 'storage "%%_name" was initialized', 'ok',
            Timer::period_get('storage init', -1, -2), ['name' => $storage->name]
        );
    }

    static function on_query_before($event, $storage, $query) {
        $query_hash = Security::hash_get($query);
        Timer::tap('storage query with hash: '.$query_hash);
    }

    static function on_query_after($event, $storage, $query, $statement, $errors) {
        if ($errors[0] === PDO::ERR_NONE) {
            $query_hash = Security::hash_get($query);
            Timer::tap('storage query with hash: '.$query_hash);
            $args_trimmed = [];
            foreach ($storage->args as $c_arg)
                $args_trimmed[] = mb_strimwidth((string)$c_arg, 0, 40, '…', 'UTF-8');
            $query_prepared = $query;
            $storage->prepare_query($query_prepared, true);
            $query_flat = Core::array_values_select_recursive($query_prepared);
            $query_flat_string = implode(' ', $query_flat).';';
            $query_beautiful = str_replace([' ,', '( ', ' )'], [',', '(', ')'], $query_flat_string);
            $query_beautiful_args = '[\''.implode('\', \'', $args_trimmed).'\']';
            $query_time = Timer::period_get('storage query with hash: '.$query_hash, -1, -2);
            if (count($storage->args))
                 Console::log_insert('storage', 'query', $query_beautiful, 'ok', $query_time, [], ['arguments' => $query_beautiful_args]);
            else Console::log_insert('storage', 'query', $query_beautiful, 'ok', $query_time, []);
        }
    }

}
