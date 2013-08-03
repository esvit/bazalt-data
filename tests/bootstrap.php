<?php

namespace tests;

require_once '../vendor/autoload.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->add('tests', __DIR__ . '/..');
$loader->register();