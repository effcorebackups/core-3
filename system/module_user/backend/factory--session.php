<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore\modules\user {
          use \effectivecore\factory as factory;
          use \effectivecore\instance as instance;
          use \effectivecore\message_factory as message;
          abstract class session_factory {

  static function id_regenerate($sign) {
    $session_id = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].rand(0, PHP_INT_MAX));
    $session_id[0] = $sign; # a - anonymous user | f - authenticated user
    setcookie('sid', ($_COOKIE['sid'] = $session_id), time() + 60 * 60 * 24 * 30, '/');
    return $session_id;
  }

  static function id_get() {
    if (isset($_COOKIE['sid'])) return $_COOKIE['sid'];
    else                        return static::id_regenerate('a');
  }

  static function select() {
    $session_id = static::id_get();
    if ($session_id[0] == 'f') {
      $session = (new instance('session', [
        'id' => $session_id
      ]))->select();
      if (!$session) {
        static::id_regenerate('a');
        message::add_new('invalid session was deleted!', 'warning');
      }
      return $session;
    }
  }

  static function insert($id_user) {
    static::id_regenerate('f');
    (new instance('session', [
      'id'      => static::id_get(),
      'id_user' => $id_user,
      'created' => factory::datetime_get()
    ]))->insert();
  }

  static function delete($id_user) {
    (new instance('session', [
      'id'      => static::id_get(),
      'id_user' => $id_user
    ]))->delete();
    static::id_regenerate('a');
  }

}}