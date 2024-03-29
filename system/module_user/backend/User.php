<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

abstract class User {

    protected static $cache;

    static function cache_cleaning() {
        static::$cache = null;
    }

    static function init($is_load_roles = true, $is_load_permissions = false) {
        if (static::$cache === null) {
            static::$cache = new Instance('user');
            static::$cache->nickname = null;
            static::$cache->id       = null;
            static::$cache->roles    = ['anonymous' => 'anonymous'];
            $session = Session::select();
            if ($session &&
                $session->id_user) {
                $user = static::select(
                    $session->id_user,
                    $is_load_roles,
                    $is_load_permissions);
                if ($user) {
                    static::$cache = $user;
                }
            }
        }
    }

    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦

    static function get_current() {
        static::init();
        return static::$cache;
    }

    static function select($id, $is_load_roles = false, $is_load_permissions = false) {
        $user = new Instance('user', ['id' => $id]);
        if ($user->select()) {
            $user->roles = $is_load_roles || $is_load_permissions ?
                ['registered' => 'registered'] + static::related_roles_select($id) :
                ['registered' => 'registered'];
            $user->permissions = $is_load_permissions ?
                Role::related_permissions_by_roles_select($user->roles) : [];
            return $user;
        }
    }

    static function select_multiple($ids, $is_load_roles = false, $is_load_permissions = false) {
        $users = Entity::get('user')->instances_select([
            'where' => [
                'id_!f'                => 'id',
                'id_in_begin_operator' => 'in (',
                'id_in_value_!v'       => $ids,
                'id_in_end_operator'   => ')']], 'id');
        foreach ($users as $c_user) {
            $c_user->roles = $is_load_roles || $is_load_permissions ?
                ['registered' => 'registered'] + static::related_roles_select($c_user->id) :
                ['registered' => 'registered'];
            $c_user->permissions = $is_load_permissions ?
                Role::related_permissions_by_roles_select($c_user->roles) : [];
        }
        return $users;
    }

    static function insert($values) {
        return (new Instance('user', $values))->insert();
    }

    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦

    static function related_roles_select($id_user) {
        $result = [];
        $items = Entity::get('relation_role_with_user')->instances_select([
            'where' => [
                'id_user_!f'       => 'id_user',
                'id_user_operator' => '=',
                'id_user_!v'       => $id_user]]);
        foreach ($items as $c_item)
            $result[$c_item->id_role] =
                    $c_item->id_role;
        return $result;
    }

    static function related_roles_insert($id_user, $roles, $module_id = null) {
        $result = [];
        foreach ($roles as $c_id_role) {
            $result[$c_id_role] = (new Instance('relation_role_with_user', [
                'id_role'   => $c_id_role,
                'id_user'   =>   $id_user,
                'module_id' => $module_id
            ]))->insert(); }
        return $result;
    }

    static function related_roles_delete($id_user) {
        return Entity::get('relation_role_with_user')->instances_delete([
            'where' => [
                'id_user_!f'       => 'id_user',
                'id_user_operator' => '=',
                'id_user_!v'       => $id_user
        ]]);
    }

    static function related_role_delete($id_user, $id_role) {
        return (new Instance('relation_role_with_user', [
            'id_user' => $id_user,
            'id_role' => $id_role
        ]))->delete();
    }

    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦

    static function key_get($name) {
        return Storage::get('data')->select('settings/user/keys/'.$name);
    }

    static function keys_install() {
        return Storage::get('data')->changes_register('user', 'update', 'settings/user/keys', [
            'cron' => Core::generate_random_bytes(40, Module::settings_get('user')->hash_characters),
            'salt' => Core::generate_random_bytes(40, Module::settings_get('user')->key_characters),
            'form' => Core::generate_random_bytes(40, Module::settings_get('user')->key_characters),
            'user' => Core::generate_random_bytes(40, Module::settings_get('user')->key_characters),
            'args' => Core::generate_random_bytes(40, Module::settings_get('user')->key_characters),
        ], true, false);
    }

    static function signature_get($string, $key_name, $length = 40) {
        $key = static::key_get($key_name);
        if ($key) return substr(hash('sha3-512', hash('sha3-512', $string).$key), 0, $length);
        else Message::insert(new Text('Key "%%_key" does not exist!', ['key' => $key_name]), 'error');
    }

    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦

    static function password_generate($length = 8) {
        return Core::generate_random_bytes($length,
            Module::settings_get('user')->password_characters
        );
    }

    static function password_hash($password) {
        return hash('sha3-512', hash('sha3-512', $password).static::key_get('salt'));
    }

    static function password_verify($password, $hash) {
        return hash_equals($hash, static::password_hash($password));
    }

}
