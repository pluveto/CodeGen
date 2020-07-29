<?php
require_once realpath(__DIR__ . "/../vendor/autoload.php");

use Pluveto\CodeGen\AutoRouter\AutoRouter;


if (count($argv) == 1) {
    AutoRouter::getInstance()->generateRouter();
    return;
}

if ($argv[1] == "api") {
    AutoRouter::getInstance()->generateApi();
    return;
}
