<?php

  ##################################################################
  ### Copyright © 2017—2019 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          use \PDO as pdo;
          use \PDOException as pdo_exception;
          class storage_sql_pdo implements has_external_cache {

  public $name;
  public $driver;
  public $credentials = [];
  public $table_prefix = '';
  public $args = [];
  public $errors = [];
  protected $queries = [];
  protected $connection;

  function init($driver = null, $credentials = []) {
    if ($this->connection) return
        $this->connection;
    else {
      if ($driver)      $this->driver      = $driver;
      if ($credentials) $this->credentials = $credentials;
      if ($this->driver &&
          $this->credentials) {
        try {
          event::start('on_storage_init_before', 'pdo', [&$this]);
          switch ($this->driver) {
            case 'mysql':
              $this->connection = new pdo(
                $this->driver.':host='.
                $this->credentials->host.';port='.
                $this->credentials->port.';dbname='.
                $this->credentials->database,
                $this->credentials->login,
                $this->credentials->password);
              break;
            case 'sqlite':
              $this->connection = new pdo(
                $this->driver.':'.data::directory.
                $this->credentials->file_name);
              $this->query('PRAGMA', 'foreign_keys', '=', 'ON');
              break;
          }
          event::start('on_storage_init_after', 'pdo', [&$this]);
          return $this->connection;
        } catch (pdo_exception $e) {
          message::insert(
            translation::get('Storage %%_name is not available!', ['name' => $this->name]), 'error'
          );
        }
      } else {
        $path = (new file(data::directory))->path_relative_get();
        $link = (new markup('a', ['href' => '/install/en'], 'Installation'))->render();
        message::insert(
          translation::get('Credentials for storage %%_name was not set!', ['name' => $this->name]).br.
          translation::get('Restore the storage credentials in "%%_path" directory or reinstall this system on the page: %%_link', ['path' => $path, 'link' => $link]), 'error'
        );
      }
    }
  }

  function test($driver, $credentials = []) {
    try {
      switch ($driver) {
        case 'mysql':
          $connection = new pdo(
            $driver.':host='.
            $credentials->host.';port='.
            $credentials->port.';dbname='.
            $credentials->database,
            $credentials->login,
            $credentials->password);
          break;
        case 'sqlite':
          $path = data::directory.$credentials->file_name;
          $connection = new pdo($driver.':'.$path);
          if (!is_writable($path)) {
            throw new \Exception('File is not writable!');
          }
          break;
      }
      $connection = null;
      return true;
    } catch (pdo_exception $e) {
      return ['message' => $e->getMessage(), 'code' => $e->getCode()]; } catch (\Exception $e) {
      return ['message' => $e->getMessage(), 'code' => $e->getCode()];
    }
  }

  function title_get() {
    if ($this->init()) {
      switch ($this->driver) {
        case 'mysql' : return 'MySQL';
        case 'sqlite': return 'SQLite';
      }
    }
  }

  function version_get() {
    if ($this->init()) {
      switch ($this->driver) {
        case 'mysql' : return $this->query('SELECT', 'version()',        'as', 'version')[0]->version;
        case 'sqlite': return $this->query('SELECT', 'sqlite_version()', 'as', 'version')[0]->version;
      }
    }
  }

  function transaction_begin()     {if ($this->init()) return $this->connection->beginTransaction();}
  function transaction_roll_back() {if ($this->init()) return $this->connection->rollBack();}
  function transaction_commit()    {if ($this->init()) return $this->connection->commit();}

  function query_to_string(...$query) {
    return implode(' ', core::array_values_select_recursive($query)).';';
  }

  function query(...$query) {
    if (is_array($query[0])) $query = $query[0];
    if ($this->init()) {
      $this->queries[] = $query;
      event::start('on_query_before', 'pdo', [&$this, &$query]);
      $result = $this->connection->prepare($this->query_to_string($query));
      if ($result) $result->execute($this->args);
      $c_error = $result ? $result->errorInfo() : ['query preparation return the false', 'no', 'no'];
      event::start('on_query_after', 'pdo', [&$this, &$query, &$result, &$c_error]);
      $this->args = [];
      if ($c_error !== ['00000', null, null]) {
        $this->errors[] = $c_error;
        message::insert(
          translation::get('Query error!').br.
          translation::get('sql state: %%_state',        ['state' => translation::get($c_error[0])]).br.
          translation::get('driver error code: %%_code', ['code'  => translation::get($c_error[1])]).br.
          translation::get('driver error text: %%_text', ['text'  => translation::get($c_error[2])]), 'error'
        );
        return null;
      }
      switch (strtoupper($query[0])) {
        case 'SELECT': return $result ? $result->fetchAll(pdo::FETCH_CLASS|pdo::FETCH_PROPS_LATE, '\\'.__NAMESPACE__.'\\instance') : null;
        case 'INSERT': return $this->connection->lastInsertId();
        case 'UPDATE': return $result->rowCount();
        case 'DELETE': return $result->rowCount();
        default      : return $result;
      }
    }
  }

  function tables(...$tables) {
    $result = [];
    foreach (is_array($tables[0]) ?
                      $tables[0] : $tables as $c_table) {
      switch ($this->driver) {
        case 'mysql' :
          $result[] = '`'.$this->table_prefix.$c_table.'`';
          $result[] = $this->op(',');
          break;
        case 'sqlite':
          $result[] = '"'.$this->table_prefix.$c_table.'"';
          $result[] = $this->op(',');
          break;
      }
    }
    array_pop($result);
    return $result;
  }

  function fields(...$fields) {
    $result = [];
    foreach (is_array($fields[0]) ?
                      $fields[0] : $fields as $c_field) {
      $result[] = $c_field;
      $result[] = $this->op(',');}
    array_pop($result);
    return $result;
  }

  function values(...$values) {
    $result = [];
    foreach (is_array($values[0]) ?
                      $values[0] : $values as $c_value) {
      $this->args[] = $c_value;
      $result[] = '?';
      $result[] = $this->op(',');
    }
    array_pop($result);
    return $result;
  }

  function condition($field, $value, $op = '=') {
    return ['field' => $this->fields($field), 'op' => $op,
            'value' => $this->values($value)];
  }

  function op($op) {
    return $op;
  }

  function attributes($data, $op = 'and') {
    $result = [];
    foreach ($data as $c_field => $c_value) {
      $result[] = is_array($c_value) ? $c_value : $this->condition($c_field, $c_value);
      $result[] = $this->op($op);}
    array_pop($result);
    return $result;
  }

  ################
  ### entities ###
  ################

  function entity_install($entity) {
    if ($this->init()) {
      $fields = [];
      foreach ($entity->fields as $c_name => $c_info) {
      # prepare field type
        $c_properties = [$c_name];
        switch ($c_info->type) {
          case 'autoincrement':
            if ($this->driver ==  'mysql') $c_properties[] = 'integer primary key auto_increment';
            if ($this->driver == 'sqlite') $c_properties[] = 'integer primary key autoincrement';
            break;
          default:
            $c_properties[] = $c_info->type.(isset($c_info->size) ?
                                               '('.$c_info->size.')' : '');
        }
      # prepare field properties
        if (property_exists($c_info, 'not_null') && $c_info->not_null) $c_properties[] = 'not null';
        if (property_exists($c_info, 'null')     && $c_info->null)     $c_properties[] = 'null';
        if (property_exists($c_info, 'default')) {
          if     ($c_info->default === 0)    $c_properties[] = 'default 0';
          elseif ($c_info->default === null) $c_properties[] = 'default null';
          else                               $c_properties[] = 'default \''.$c_info->default.'\'';
        }
        $fields[] = $c_properties;
      }
    # prepare constraints
      $auto_name = $entity->auto_name_get();
      foreach ($entity->constraints as $c_name => $c_info) {
        if ($c_info->fields != [$auto_name => $auto_name]) {
          $c_constraint_name = $this->tables($entity->catalog_name.'__'.$c_name);
          if ($c_info->type == 'primary') $fields[] = ['CONSTRAINT', $c_constraint_name, 'PRIMARY KEY', '(', $this->fields($c_info->fields), ')'];
          if ($c_info->type ==  'unique') $fields[] = ['CONSTRAINT', $c_constraint_name, 'UNIQUE',      '(', $this->fields($c_info->fields), ')'];
          if ($c_info->type == 'foreign') $fields[] = ['CONSTRAINT', $c_constraint_name, 'FOREIGN KEY', '(', $this->fields($c_info->fields), ')', 'REFERENCES', $c_info->references, '(', $this->fields($c_info->references_fields), ')', 'ON', 'UPDATE', $c_info->on_update ?? 'cascade', 'ON', 'DELETE', $c_info->on_delete ?? 'cascade'];
        }
      }
    # create entity
      $table_name = $this->tables($entity->catalog_name);
      $this->transaction_begin();
      if ($this->driver ==  'mysql') $this->query('SET', 'FOREIGN_KEY_CHECKS', '=', '0');
      if ($this->driver == 'sqlite') $this->query('PRAGMA', 'foreign_keys', '=',  'OFF');
                                     $this->query('DROP', 'TABLE', 'IF EXISTS', $table_name);
      if ($this->driver ==  'mysql') $this->query('CREATE', 'TABLE', $table_name, '(', $this->fields($fields), ')', 'CHARSET', '=', 'utf8');
      if ($this->driver == 'sqlite') $this->query('CREATE', 'TABLE', $table_name, '(', $this->fields($fields), ')');
      if ($this->driver ==  'mysql') $this->query('SET', 'FOREIGN_KEY_CHECKS', '=', '1');
      if ($this->driver == 'sqlite') $this->query('PRAGMA', 'foreign_keys', '=',   'ON');
    # create indexes
      foreach ($entity->indexes as $c_name => $c_info) {
        $c_index_name = $this->tables($entity->catalog_name.'__'.$c_name);
        $this->query('CREATE', $c_info->type, $c_index_name, 'ON', $table_name, '(', $this->fields($c_info->fields), ')');
      }
      return $this->transaction_commit();
    }
  }

  function entity_uninstall($entity) {
    if ($this->init()) {
      if ($this->driver ==  'mysql') $this->query('SET', 'FOREIGN_KEY_CHECKS', '=', '0');
      if ($this->driver == 'sqlite') $this->query('PRAGMA', 'foreign_keys', '=',  'OFF');
      $result =                      $this->query('DROP', 'TABLE', $this->tables($entity->catalog_name));
      if ($this->driver ==  'mysql') $this->query('SET', 'FOREIGN_KEY_CHECKS', '=', '1');
      if ($this->driver == 'sqlite') $this->query('PRAGMA', 'foreign_keys', '=',   'ON');
      return $result;
    }
  }

  function instances_select($entity, $conditions = [], $order = [], $limit = 0, $offset = 0) {
    if ($this->init()) {
      $query = ['SELECT', '*', 'FROM', $this->tables($entity->catalog_name)];
      if (count($conditions)) array_push($query, 'WHERE',       $this->attributes($conditions));
      if (count($order))      array_push($query, 'ORDER', 'BY', $this->fields($order));
      if ($limit)             array_push($query, 'LIMIT', $limit);
      if ($offset)            array_push($query, 'OFFSET', $offset);
      $result = $this->query($query);
      foreach ($result as $c_instance) {
        $c_instance->entity_name_set($entity->name);
      }
      return $result;
    }
  }

  function instance_select($instance) { # return: null | instance
    if ($this->init()) {
      $entity    = $instance->entity_get();
      $id_fields = $entity->real_id_from_values_get($instance->values_get());
      $result    = $this->query(
        'SELECT', $this->fields($entity->fields_name_get()),
        'FROM',   $this->tables($entity->catalog_name),
        'WHERE',  $this->attributes($id_fields), 'LIMIT', 1);
      if (isset($result[0])) {
        foreach ($result[0]->values as $c_name => $c_value) {
          $instance->{$c_name} = $c_value;
          $instance->_id_fields_original = $id_fields;
        }
        return $instance;
      }
    }
  }

  function instance_insert($instance) { # return: null | instance | instance + new_id
    if ($this->init()) {
      $entity = $instance->entity_get();
      $values = array_intersect_key($instance->values_get(), $entity->fields_name_get());
      $fields = array_keys($values);
      $auto_name = $entity->auto_name_get();
      $new_id = $this->query(
        'INSERT', 'INTO', $this->tables($entity->catalog_name), '(',
                          $this->fields($fields), ')',
        'VALUES', '(',    $this->values($values), ')');
      if ($new_id !== null && $auto_name == null) return $instance;
      if ($new_id !== null && $auto_name != null) {
        $instance->{$auto_name} = $new_id;
        return $instance;
      }
    }
  }

  function instance_update($instance) { # return: null | instance
    if ($this->init()) {
      $entity    = $instance->entity_get();
      $id_fields = $entity->real_id_from_values_get($instance->values_get());
      $values    = array_intersect_key($instance->values_get(), $entity->fields_name_get());
      $row_count = $this->query(
        'UPDATE', $this->tables($entity->catalog_name),
        'SET',    $this->attributes($values, ','),
        'WHERE',  $this->attributes($instance->_id_fields_original ?: $id_fields));
      if ($row_count === 1) {
        $instance->_id_fields_original = $id_fields;
        return $instance;
      }
    }
  }

  function instance_delete($instance) { # return: null | instance + empty(values)
    if ($this->init()) {
      $entity    = $instance->entity_get();
      $id_fields = $entity->real_id_from_values_get($instance->values_get());
      $row_count = $this->query(
        'DELETE', 'FROM', $this->tables($entity->catalog_name),
        'WHERE',          $this->attributes($id_fields));
      if ($row_count === 1) {
        $instance->values_set([]);
        return $instance;
      }
    }
  }

  ###########################
  ### static declarations ###
  ###########################

  static function not_external_properties_get() {
    return ['name' => 'name'];
  }

}}