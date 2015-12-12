<?php

namespace Gsandbox;

class LimitExceededException extends \Exception { };

class Vault {

  const DATEFORMAT = 'Y-m-d\TH:i:s\Z';

  public $name;

  public function __construct($name) {
    $this->name = $name;
  }

  public static function all() {
    $dir = $GLOBALS['config']['storePath'] . "/vaults";

    $ret = [];

    foreach (glob("$dir/*", GLOB_ONLYDIR) as $dir) {
      if ($v = Vault::get(basename($dir))) {
        $ret[] = $v;
      }
    }

    return $ret;
  }

  public static function get($name) {
    $dir = $GLOBALS['config']['storePath'] . "/vaults/$name";
    if (!is_dir($dir)) {
      return false;
    }

    $v = new Vault($name);
    return $v;
  }

  public static function create($name) {
    $dir = $GLOBALS['config']['storePath'] . "/vaults/$name";
    if (is_dir($dir)) {
      return true;
    }
    @mkdir($dir, 0777, true);
    return Vault::get($name);
  }

  public function delete() {
    foreach ($this->getMultiparts() as $m) {
      $m->delete();
    }
    foreach ($this->getJobs() as $j) {
      $j->delete();
    }
    foreach ($this->getArchives() as $a) {
      $a->delete();
    }
    @rmdir($this->getDir() . '/multiparts');
    @rmdir($this->getDir() . '/archives');
    @rmdir($this->getDir() . '/jobs');
    @unlink($this->getDir() . '/tags');
    rmdir($this->getDir());
  }

  public function getDir() {
    return $GLOBALS['config']['storePath'] . "/vaults/{$this->name}";
  }

  public function getARN() {
    return 'FAKEARN/' . $this->name;
  }

  public function getCreationDate() {
    $date = new \DateTime();
    $date->setTimezone(new \DateTimeZone("UTC"));
    $date->setTimestamp(filemtime($this->getDir()));
    return $date;
  }

  public function getNumberOfArchives() {
    $numArchives = 0;
    foreach (glob($this->getDir() . '/archives/*') as $f) {
      $numArchives += 1;
    }
    return $numArchives;
  }

  public function getSizeInBytes() {
    $size = 0;
    foreach (glob($this->getDir() . '/archives/*') as $dir) {
      foreach (glob($dir . '/*') as $f) {
        $size += filesize($f);
      }
    }
    return $size;
  }

  public function serializeArray() {
    return [
      "VaultName" => $this->name,
      "CreationDate" => $this->getCreationDate()->format(Vault::DATEFORMAT),
      "LastInventoryDate" => null,
      "NumberOfArchives" => $this->getNumberOfArchives(),
      "SizeInBytes" => $this->getSizeInBytes(),
      "VaultARN" => $this->getARN(),
    ];
  }

  public function createMultipart($partSize, $desc = '') {
    $ret = new Multipart(true, $this);
    $ret->initiate($partSize, $desc);
    return $ret;
  }

  public function getMultipart($id) {
    $ret = new Multipart($id, $this);
    if (!$ret->exists()) {
      return false;
    }
    return $ret;
  }

  public function getMultiparts() {
    $ret = [];

    foreach (glob($this->getDir() . '/multiparts/*', GLOB_ONLYDIR) as $d) {
      $ret[] = $this->getMultipart(basename($d));
    }

    return $ret;
  }

  public function createJob($params) {
    $ret = new Job(true, $this);
    $ret->setParams($params);
    return $ret;
  }

  public function getJobs() {
    $ret = [];

    foreach (glob($this->getDir() . '/jobs/*', GLOB_ONLYDIR) as $d) {
      if ($job = $this->getJob(basename($d))) {
        $ret[] = $job;
      }
    }

    return $ret;
  }

  public function getJob($id) {
    if (($ret = Job::get($id, $this)) === false) {
      return false;
    }

    if ($ret->hasExpired()) {
      $ret->delete();
      return false;
    }

    if (!$ret->exists()) {
      return false;
    }

    return $ret;
  }

  public function getArchives() {
    $ret = [];

    foreach (glob($this->getDir() . '/archives/*', GLOB_ONLYDIR) as $d) {
      if ($archive = $this->getArchive(basename($d))) {
        $ret[] = $archive;
      }
    }

    return $ret;
  }

  public function getArchive($id) {
    $ret = new Archive($id, $this);
    if (!$ret->exists()) {
      return false;
    }
    return $ret;
  }

  public function getTagsFile() {
    return $this->getDir() . '/tags';
  }

  public function addTags($tags) {
    $allTags = $this->getTags();

    if (!is_array($allTags)) {
      $allTags = [];
    }

    $allTags = array_merge($tags, $allTags);

    $this->setTags($allTags);
  }

  public function setTags($tags) {
    if (count($tags) > 10) {
      throw new LimitExceededException;
    }

    file_put_contents($file = $this->getTagsFile(), '<?php return ' . var_export($tags, true) . ';');
    opcache_invalidate($file);
  }

  public function removeTags($removeKeys) {
    $allTags = $this->getTags();

    if (!is_array($allTags)) {
      $allTags = [];
    }

    $allTags = array_filter($allTags, function ($value, $key) use ($removeKeys) {
      return in_array($key, $removeKeys) === false;
    }, ARRAY_FILTER_USE_BOTH);

    $this->setTags($allTags);
  }

  public function getTags() {
    if (!file_exists($this->getTagsFile())) {
      return [];
    }

    return include($this->getTagsFile());
  }

}

