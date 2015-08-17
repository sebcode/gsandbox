<?php

namespace Gsandbox;

class Request {

  public $accessKey = false;
  public $method = '';
  public $uri = '';
  public $path = '';
  public $pathParts = [];

  public function __construct() {
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->uri = $_SERVER['REQUEST_URI'];

    $path = rtrim($this->uri, '?');
    $path = trim($path, '/');
    $pathParts = explode('/', $path);
    array_shift($pathParts);
    $path = implode('/', $pathParts);
    $this->path = $path;
    $this->pathParts = $pathParts;

    if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
      #throw new \Exception('Authorization header missing');
      return;
    }

    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (!preg_match('@Credential=([A-Z0-9]+)@', $auth, $m)) {
      #throw new \Exception('Invalid Authorization header');
      return;
    }

    $this->accessKey = $m[1];
  }

  public function getRange() {
    if (empty($_SERVER['HTTP_RANGE'])) {
      return false;
    }

    $range = $_SERVER['HTTP_RANGE'];

    if (!preg_match('@([0-9]+)?-([0-9]+)@', $range, $m)) {
      return false;
    }

    return [ $m[1], $m[2] ];
  }

}

