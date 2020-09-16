<?php

  ##################################################################
  ### Copyright © 2017—2021 Maxim Rysevets. All rights reserved. ###
  ##################################################################

namespace effcore {
          class step_check {

  public $check;
  public $where;
  public $match;
  public $on_success;
  public $on_failure;

  function run(&$test, $dpath, &$c_results) {
    if ($this->check === 'token') $result = (string)token::apply($this->where) === (string)$this->match;
    if ($result === true) $c_results['reports'][$dpath.'/on_success_title'] = new text_multiline(['checking on "%%_name" = "%%_value"', 'result of checking is = "%%_result"'], ['name' => $this->check.($this->where ? ': '.$this->where : ''), 'value' => $this->match, 'result' => (new text('success'))->render() ]);
    if ($result !== true) $c_results['reports'][$dpath.'/on_failure_title'] = new text_multiline(['checking on "%%_name" = "%%_value"', 'result of checking is = "%%_result"'], ['name' => $this->check.($this->where ? ': '.$this->where : ''), 'value' => $this->match, 'result' => (new text('failure'))->render() ]);
    if ($result === true && isset($this->on_success)) foreach ($this->on_success as $c_step) { $c_step->run($test, $dpath.'/on_success', $c_results); if (array_key_exists('return', $c_results)) return; }
    if ($result !== true && isset($this->on_failure)) foreach ($this->on_failure as $c_step) { $c_step->run($test, $dpath.'/on_failure', $c_results); if (array_key_exists('return', $c_results)) return; }
  }

}}