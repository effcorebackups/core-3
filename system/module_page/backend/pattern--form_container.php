<?php

  #############################################################
  ### Copyright © 2017 Maxim Rysevets. All rights reserved. ###
  #############################################################

namespace effectivecore {
          class form_container extends \effectivecore\markup {

  public $tag_name = 'x-container';
  public $template = 'form_container';
  public $title = '';
  public $description = '';

  function __construct($tag_name = '', $title = '', $description = '', $attributes = [], $children = [], $weight = 0) {
    if ($title)       $this->title       = $title;
    if ($description) $this->description = $description;
    parent::__construct($tag_name, $attributes, $children, $weight);
  }

  function render() {
    return (new template($this->template, [
      'attributes'  => factory::data_to_attr($this->attribute_select()),
      'tag_name'    => $this->tag_name,
      'title'       => $this->render_self(),
      'content'     => $this->render_children($this->children),
      'description' => $this->render_description()
    ]))->render();
  }

  function render_self() {
    if ($this->title) {
      $required_mark = $this->attribute_select('required') ? $this->render_required_mark() : '';
      return (new markup('x-title', [], [
        $this->title, $required_mark
      ]))->render();
    }
  }

}}