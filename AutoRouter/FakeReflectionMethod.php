<?php

namespace Pluveto\CodeGen\AutoRouter;

class FakeReflectionMethod
{

    private $methodName;
    private $comments;
    public function __construct($methodName, $comments)
    {
        $this->methodName = $methodName;
        $this->comments = $comments;
    }

    public function getName()
    {
        return $this->methodName;
    }

    public function getDocComment(){
        return $this->comments;
    }
}
