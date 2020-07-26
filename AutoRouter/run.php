<?php
require_once realpath( __DIR__."/../vendor/autoload.php");

use Pluveto\CodeGen\AutoRouter\AutoRouter;


echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~";

AutoRouter::getInstance()->generateRouter();
