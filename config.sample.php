<?php

return [
  'storePath' => '/var/gsandboxstore/',

  'responseDelay' => function () {
    #usleep(1000000 / 2);
  },

  'uploadThrottle' => function () {
    usleep(1000000 / 4);
  },

  'downloadThrottle' => function () {
    #usleep(1000000 * 0.5);
    #sleep(1);
  },

  //'inventoryComplete' => '+10 second',

  //'throwPolicyEnforcedException' => true,

  //'throwResourceNotFoundExceptionForGetJobOutput' => true,

  //'throwThrottlingExceptionForUpload' => true,

  //'throwThrottlingExceptionForListMultiparts' => function () {
  //  return rand(0, 4) === 0;
  //},

];
