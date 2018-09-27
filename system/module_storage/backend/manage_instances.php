<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          abstract class manage_instances {

  # function() ←→ url mapping:
  # ─────────────────────────────────────────────────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
  # instance_select_multiple_by_entity_name()        │ /manage/instances/action_select → /manage/instances/action_select/%%_entity_name
  # instance_insert_by_entity_name()                 │ /manage/instances/action_insert → /manage/instances/action_insert/%%_entity_name
  # instance_select_by_entity_name_and_instance_id() │                                   /manage/instances/action_select/%%_entity_name/%%_instance_id_1.1/…/%%_instance_id_1.N
  # instance_update_by_entity_name_and_instance_id() │                                   /manage/instances/action_update/%%_entity_name/%%_instance_id_1.1/…/%%_instance_id_1.N
  # instance_delete_by_entity_name_and_instance_id() │                                   /manage/instances/action_delete/%%_entity_name/%%_instance_id_1.1/…/%%_instance_id_1.N
  # ─────────────────────────────────────────────────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────

  # ─────────────────────────────────────────────────────────────────────
  # select instances and single instance
  # ─────────────────────────────────────────────────────────────────────

  static function instance_select_multiple_by_entity_name($page) {
    $entities = entity::all_get(false);
  # create tabs
    core::array_sort_by_property($entities, 'title');
    foreach ($entities as $c_entity) {
      tabs::item_insert($c_entity->title_plural,
        'instance_select_'.$c_entity->name, # - id
        'instance_select',                  # - id parent
                 'select/'.$c_entity->name  # - suffix for url
      );
    }
  # create selection
    $entity = entity::get($page->args_get('entity_name'));
    if ($entity) {
      $selection = new selection;
      foreach ($entity->fields as $c_name => $c_info) {
        if (!isset($c_info->hidden) ||
                  !$c_info->hidden) {
          $selection->field_insert($entity->name, $c_name);
        }
      }
      $markup = $selection->build();
      return new block('', ['class' => [$entity->name => $entity->name]],
        $markup
      );
    } else {
      url::go(
        $page->args_get('base').'/select/'.reset($entities)->name
      );
    }
  }

  static function instance_select_by_entity_name_and_instance_id($page) {
  # create selection
    $entity = entity::get($page->args_get('entity_name'));
    if ($entity) {
    # @todo: make functionality
      return new text('instance_select is UNDER CONSTRUCTION');
    } else {
      url::go(
        $page->args_get('base').'/select'
      );
    }
  }

  # ─────────────────────────────────────────────────────────────────────
  # insert single instance
  # ─────────────────────────────────────────────────────────────────────

  static function instance_insert_by_entity_name($page) {
    $entities = entity::all_get(false);
  # create tabs
    core::array_sort_by_property($entities, 'title');
    foreach ($entities as $c_entity) {
      tabs::item_insert($c_entity->title,
        'instance_insert_'.$c_entity->name, # - id
        'instance_insert',                  # - id parent
                 'insert/'.$c_entity->name  # - suffix for url
      );
    }
  # create insert form
    $entity = entity::get($page->args_get('entity_name'));
    if ($entity) {
    # @todo: make functionality
      return new text('instance_insert is UNDER CONSTRUCTION');
    } else {
      url::go(
        $page->args_get('base').'/insert/'.reset($entities)->name
      );
    }
  }

  # ─────────────────────────────────────────────────────────────────────
  # update single instance
  # ─────────────────────────────────────────────────────────────────────

  static function instance_update_by_entity_name_and_instance_id($page) {
    $entity = entity::get($page->args_get('entity_name'));
    if ($entity) {
      $tab = tabs::item_select('instance_update');
      $tab->action_name = 'update/'.$entity->name;
      $tab->hidden = false;
    # @todo: make functionality
      return new text('instance_update is UNDER CONSTRUCTION');
    } else {
      url::go(
        $page->args_get('base').'/select'
      );
    }
  }

  # ─────────────────────────────────────────────────────────────────────
  # delete single instance
  # ─────────────────────────────────────────────────────────────────────

  static function instance_delete_by_entity_name_and_instance_id($page) {
    $entity = entity::get($page->args_get('entity_name'));
    if ($entity) {
      $tab = tabs::item_select('instance_delete');
      $tab->action_name = 'delete/'.$entity->name;
      $tab->hidden = false;
    # @todo: make functionality
      return new text('instance_delete is UNDER CONSTRUCTION');
    } else {
      url::go(
        $page->args_get('base').'/select'
      );
    }
  }

}}
