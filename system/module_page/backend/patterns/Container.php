<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

#[\AllowDynamicProperties]

class Container extends Markup {

    public $tag_name = 'x-container';
    public $template = 'container';
    # ◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦◦
    public $title;
    public $title_tag_name = 'x-title';
    public $title_position = 'top';
    public $title_attributes = [];
    public $title_is_visible = true;
    public $title_is_apply_translation = true;
    public $title_is_apply_tokens = false;
    public $content_tag_name;
    public $content_attributes = [];
    public $description;
    public $description_tag_name = 'x-description';
    public $description_position = 'bottom';

    function __construct($tag_name = null, $title = null, $description = null, $attributes = [], $children = [], $weight = +0) {
        if ($title !== null) $this->title       = $title;
        if ($description   ) $this->description = $description;
        parent::__construct($tag_name, $attributes, $children, $weight);
    }

    function render() {
        $is_title_in_footer       = !empty($this->title_position)       && $this->title_position       === 'bottom';
        $is_description_in_header = !empty($this->description_position) && $this->description_position === 'top';
        return (Template::make_new(Template::pick_name($this->template), [
            'tag_name'           => $this->tag_name,
            'attributes'         => $this->render_attributes(),
            'title_header'       => $is_title_in_footer       ? '' : $this->render_self(),
            'title_footer'       => $is_title_in_footer       ?      $this->render_self()        : '',
            'description_header' => $is_description_in_header ?      $this->render_description() : '',
            'description_footer' => $is_description_in_header ? '' : $this->render_description(),
            'children' => $this->content_tag_name ?
                (new Markup($this->content_tag_name, $this->content_attributes, $this->render_children($this->children_select(true)) ))->render() :
                                                                                $this->render_children($this->children_select(true))
        ]))->render();
    }

    function render_self() {
        if ($this->title && (bool)$this->title_is_visible !== true) return (new Markup($this->title_tag_name, $this->title_attributes + ['data-mark-required' => $this->attribute_select('required') ? true : null, 'aria-hidden' => 'true'], is_string($this->title) ? new Text($this->title, [], $this->title_is_apply_translation, $this->title_is_apply_tokens) : $this->title))->render();
        if ($this->title && (bool)$this->title_is_visible === true) return (new Markup($this->title_tag_name, $this->title_attributes + ['data-mark-required' => $this->attribute_select('required') ? true : null                         ], is_string($this->title) ? new Text($this->title, [], $this->title_is_apply_translation, $this->title_is_apply_tokens) : $this->title))->render();
    }

    function render_description() {
        $this->description = static::description_prepare($this->description);
        if (count($this->description)) {
            return (new Markup($this->description_tag_name, [], $this->description))->render();
        }
    }

    # ─────────────────────────────────────────────────────────────────────
    # functionality for errors
    # ─────────────────────────────────────────────────────────────────────

    function has_error_in($root = null) {
        foreach (($root ?: $this)->children_select_recursive() as $c_child) {
            if ($c_child instanceof Field &&
                $c_child->has_error()) {
                return true;
            }
        }
    }

    function error_set_in($root = null) {
        foreach (($root ?: $this)->children_select_recursive() as $c_child) {
            if ($c_child instanceof Field) {
                $c_child->error_set();
            }
        }
    }

}
