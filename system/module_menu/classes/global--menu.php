<?php

namespace effectivecore {
          class menu extends \effectivecore\dom_node {

  public $title;

  function __construct($title = '', $attributes = null, $children = null, $weight = 0) {
    parent::__construct($attributes, $children, $weight);
    $this->title = $title;
  }

  function render() {
    $rendered_children = (new template('menu_children', [
      'children' => $this->render_children($this->children)
    ]))->render();
    return (new template('menu', [
      'attributes' => factory::data_to_attr($this->attributes, ' '),
      'self'       => $this->render_self(),
      'children'   => $rendered_children
    ]))->render();
  }

  protected function render_self() {
    return (new template('menu_self', [
      'title' => $this->title
    ]))->render();
  }

}}