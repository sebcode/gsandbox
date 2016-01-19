<?php

namespace Gsandbox;

abstract class VaultEntity {

  public $id;
  public $vault;

  abstract public function getSubdir();

  public function __construct($id, $vault) {
    $this->vault = $vault;

    if ($id === true) {
      $this->id = md5(time().rand());
      @mkdir($this->getDir(), 0777, true);
    } else {
      $this->id = $id;
    }
  }

  public function exists() {
    return is_dir($this->getDir());
  }

  public function getDir($subdir = '') {
    return $this->vault->getDir() . '/' . $this->getSubdir() . '/' . $this->id . "/$subdir";
  }

  public function getFile($name) {
    return $this->getDir($name);
  }

  public function getParamsFile() {
    return $this->getFile('params');
  }

  public function setParams($params) {
    file_put_contents($file = $this->getParamsFile(), '<?php return ' . var_export($params, true) . ';');
    if (function_exists('opcache_invalidate')) {
      opcache_invalidate($file);
    }
  }

  public function setParam($name, $value) {
    $params = [];
    $file = $this->getParamsFile();

    if (file_exists($file)) {
      $params = include($file);
    }

    $params[$name] = $value;

    $this->setParams($params);
  }

  public function getParam($name) {
    if (!file_exists($this->getParamsFile())) {
      return null;
    }

    $params = include($this->getParamsFile());
    if (empty($params[$name])) {
      return null;
    }
    return $params[$name];
  }

  public function delete() {
    foreach (glob($this->getDir() . '/*') as $file) {
      @unlink($file);
    }
    rmdir($this->getDir());
  }

  public function getCreationDate() {
    $date = new \DateTime();
    $date->setTimezone(new \DateTimeZone("UTC"));
    $date->setTimestamp(filemtime($this->getDir()));
    return $date;
  }

}

