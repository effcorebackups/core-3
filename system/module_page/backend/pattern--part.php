<?php

  ##################################################################
  ### Copyright © 2017—2020 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class part extends node {

  public $display;
  public $type; # code | link | text | …
  public $source;
  public $properties = [];
  public $args       = [];

  function __construct($weight = 0) {
    parent::__construct([], [], $weight);
  }

  function markup_get($page = null) {
    if (!isset($this->display) ||
        (isset($this->display) && $this->display->check == 'page_args' && preg_match($this->display->match,        $page->args_get($this->display->where))) ||
        (isset($this->display) && $this->display->check == 'user'      &&            $this->display->where == 'role' && preg_match($this->display->match.'m', implode(nl, user::get_current()->roles)))) {
      switch ($this->type) {
        case 'copy':
        case 'link': if ($this->type == 'copy') $result = core::deep_clone(storage::get('files')->select($this->source, true));
                     if ($this->type == 'link') $result =                  storage::get('files')->select($this->source, true);
                     foreach ($this->properties as $c_key => $c_value)
                       core::arrobj_insert_value($result, $c_key, $c_value);
                     return $result;
        case 'code': return call_user_func_array($this->source, ['page' => $page, 'args' => $this->args]);
        case 'text': return new text($this->source);
        default    : return          $this->source;
      }
    }
  }

}}