<?php

##################################################################
### Copyright © 2017—2024 Maxim Rysevets. All rights reserved. ###
##################################################################

namespace effcore;

#[\AllowDynamicProperties]

class Control extends Container {

    public $name_prefix = '';
    public $cform;
    public $entity_name;
    public $entity_field_name;
    public $is_validate_uniqueness = false;
    protected $initial_value;

    function value_get()       {} # abstract method
    function value_set($value) {  # abstract method
        $this->value_set_initial($value);
    }

    function value_get_initial() {
        return $this->initial_value;
    }

    function value_set_initial($value, $reset = false) {
        if ($this->initial_value === null || $reset)
            $this->initial_value = $value;
    }

    function value_is_unique_in_storage_sql($value) { # @return: null | false | Instance
        if ($this->entity_name &&
            $this->entity_field_name) {
            $result = Entity::get($this->entity_name)->instances_select([
                'where' => [
                    'field_!f' => $this->entity_field_name,
                    'operator' => '=',
                    'value_!v' => $value],
                'limit' => 1]);
            return reset($result);
        }
    }

}
