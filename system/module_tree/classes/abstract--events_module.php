<?php

namespace effectivecore\modules\tree {
          use \effectivecore\factory;
          use \effectivecore\settings_factory;
          abstract class events_module extends \effectivecore\events_module {

  static function on_init() {
  # link all parents for tree_items
    foreach (settings_factory::$data['tree_items'] as $c_items) {
      foreach ($c_items as $item_id => $c_item) {
        if (!empty($c_item->parent)) {
          $c_parent = factory::npath_get_object($c_item->parent, settings_factory::$data);
          if ($c_parent) {
            $c_parent->children[$item_id] = $c_item;
          }
        }
      }
    }
  }

}}