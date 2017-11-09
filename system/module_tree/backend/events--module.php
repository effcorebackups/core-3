<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore\modules\tree {
          use \effectivecore\factory;
          use \effectivecore\trees_factory as trees;
          use \effectivecore\message_factory as message;
          use \effectivecore\entities_factory as entities;
          use \effectivecore\translations_factory as translations;
          abstract class events_module extends \effectivecore\events_module {

  static function on_start() {
    trees::init();
    foreach(trees::get_tree_items() as $c_item) {
      if ($c_item->id_parent) {
        $c_parent = !empty($c_item->parent_is_tree) ?
           trees::get_tree($c_item->id_parent) :
           trees::get_tree_item($c_item->id_parent);
        $c_parent->child_insert($c_item, $c_item->id);
      }
    };
  }

  static function on_install() {
    foreach (entities::get_by_module('tree') as $c_entity) {
      if ($c_entity->install()) message::add_new(translations::get('Entity %%_name was installed.',     ['name' => $c_entity->get_name()]));
      else                      message::add_new(translations::get('Entity %%_name was not installed!', ['name' => $c_entity->get_name()]), 'error');
    }
  }

}}