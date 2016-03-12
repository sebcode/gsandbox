<?php

namespace Gsandbox;

class InvalidPolicyException extends \Exception { };

class DataRetrievalPolicy {

  const STRATEGIES = ['BytesPerHour', 'FreeTier', 'None'];

  protected $file = '';

  public function __construct() {
    $this->file = $GLOBALS['config']['storePath'] . 'dataRetrievalPolicy.json';
  }

  public function set($ruleset) {
    $strategy = '';
    $bytesPerHour = '';

    if (empty($ruleset['Policy']['Rules'][0]['Strategy'])) {
      throw new InvalidPolicyException('Mandatory Strategy missing.');
    } else {
      $strategy = $ruleset['Policy']['Rules'][0]['Strategy'];

      if (!in_array($strategy, self::STRATEGIES, true)) {
        throw new InvalidPolicyException('Invalid Strategy.');
      }
    }

    if (!empty($ruleset['Policy']['Rules'][0]['BytesPerHour'])) {
      if ($strategy !== 'BytesPerHour') {
        throw new InvalidPolicyException('BytesPerHour field is only allowed for Strategy BytesPerHour.');
      }

      $bytesPerHour = $ruleset['Policy']['Rules'][0]['BytesPerHour'];

      if (!is_numeric($bytesPerHour)) {
        throw new InvalidPolicyException('BytesPerHour field must be numeric.');
      }
    }

    return file_put_contents($this->file, json_encode($ruleset)) !== false;
  }

  public function get() {
    if (!file_exists($this->file)) {
      return json_decode('{"Policy":{"Rules":[{"BytesPerHour":null,"Strategy":"FreeTier"}]}}', true);
    }

    return json_decode(file_get_contents($this->file), true);
  }

}

