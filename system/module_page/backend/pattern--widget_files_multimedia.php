<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class widget_files_multimedia extends widget_files_pictures {

  public $title = 'Multimedia';
  public $item_title = 'File';
  public $attributes = ['data-type' => 'items-files-multimedia'];
  public $name_complex = 'widget_files_multimedia';
# ─────────────────────────────────────────────────────────────────────
  public $upload_dir = 'multimedia/';
  public $fixed_name = 'multimedia-multiple-%%_item_id_context';
  public $max_file_size = '10M';
  public $types_allowed = [
    'mp3'  => 'mp3',
    'png'  => 'png',
    'gif'  => 'gif',
    'jpg'  => 'jpg',
    'jpeg' => 'jpeg'];
  public $thumbnails_is_visible = true;
  public $thumbnails_allowed = [];
# ─────────────────────────────────────────────────────────────────────
  public $player_audio_is_visible = true;
  public $player_audio_controls = true;
  public $player_audio_preload = 'metadata';
  public $player_audio_name = 'default';
  public $player_audio_timeline_is_visible = 'false';

  function widget_manage_get($item, $c_row_id) {
    $widget = parent::widget_manage_get($item, $c_row_id);
    $widget->attribute_insert('data-is-new', $item->object->get_current_state() === 'pre' ? 'true' : 'false');
    if ($this->thumbnails_is_visible) {
      if (in_array($item->object->type, ['picture', 'png', 'gif', 'jpg', 'jpeg'])) {
        $thumbnail_markup = new markup_simple('img', ['src' => '/'.$item->object->get_current_path(true).'?thumb=small', 'alt' => new text('thumbnail'), 'width' => '44', 'height' => '44', 'data-type' => 'thumbnail'], +450);
        $widget->child_insert($thumbnail_markup, 'thumbnail');
      }
    }
    if ($this->player_audio_is_visible) {
      if ($item->object->type === 'mp3') {
        $player_markup = new markup('audio', ['src' => '/'.$item->object->get_current_path(true), 'controls' => $this->player_audio_controls, 'preload' => $this->player_audio_preload, 'data-player-name' => $this->player_audio_name, 'data-player-timeline-is-visible' => $this->player_audio_timeline_is_visible], [], +450);
        $widget->child_insert($player_markup, 'player');
      }
    }
    return $widget;
  }

  function widget_insert_get() {
    $widget = new markup('x-widget', [
      'data-type' => 'insert']);
  # control for upload new file
    $field_file = new field_file;
    $field_file->title = 'File';
    $field_file->max_file_size    = $this->max_file_size;
    $field_file->types_allowed    = $this->types_allowed;
    $field_file->cform            = $this->cform;
    $field_file->min_files_number = null;
    $field_file->max_files_number = null;
    $field_file->has_on_validate  = false;
    $field_file->build();
    $field_file->multiple_set();
    $field_file->name_set($this->name_get_complex().'__file[]');
    $this->controls['#file'] = $field_file;
  # button for insertion of the new item
    $button = new button(null, ['data-style' => 'narrow-insert', 'title' => new text('insert')]);
    $button->break_on_validate = true;
    $button->build();
    $button->value_set($this->name_get_complex().'__insert');
    $button->_type = 'insert';
    $this->controls['~insert'] = $button;
  # grouping of previous elements in widget 'insert'
    $widget->child_insert($field_file, 'file');
    $widget->child_insert($button, 'button');
    return $widget;
  }

  ###########################
  ### static declarations ###
  ###########################

  static function complex_value_to_markup($complex) {  
    $decorator = new decorator;
    $decorator->id = 'widget_files-pictures-items';
    $decorator->view_type = 'template';
    $decorator->template = 'content';
    $decorator->template_row = 'gallery_row';
    $decorator->template_row_mapping = core::array_kmap(['num', 'type', 'children']);
    if ($complex) {
      core::array_sort_by_weight($complex);
      foreach ($complex as $c_row_id => $c_item) {
        if (in_array($c_item->object->type, ['picture', 'png', 'gif', 'jpg', 'jpeg'])) {
          $decorator->data[$c_row_id] = [
            'type'     => ['value' => 'picture'],
            'num'      => ['value' => $c_row_id],
            'children' => ['value' => new markup('a', ['data-type' => 'picture-wrapper', 'title' => new text('click to open in new window'), 'target' => 'widget_files-pictures-items', 'href' => '/'.$c_item->object->get_current_path(true).'?thumb=big'], new markup_simple('img', ['src' => '/'.$c_item->object->get_current_path(true).'?thumb=middle', 'alt' => new text('thumbnail')]))]
          ];
        }
        if ($c_item->object->type === 'mp3') {
          $decorator->data[$c_row_id] = [
            'type'     => ['value' => 'audio'  ],
            'num'      => ['value' => $c_row_id],
            'children' => ['value' => new markup('audio', ['src' => '/'.$c_item->object->get_current_path(true), 'controls' => true, 'preload' => 'metadata', 'data-player-name' => 'default', 'data-player-timeline-is-visible' => 'true'])]
          ];
        }
      }
    }
    return $decorator;
  }

}}