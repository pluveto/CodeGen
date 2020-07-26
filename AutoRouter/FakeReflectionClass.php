<?php

namespace Pluveto\CodeGen\AutoRouter;


/**
 * 该类通过形式表达式匹配的方式解析注释、函数名。
 * @author Pluveto <i@pluvet.com>
 * @copyright 2020 Incolore Team
 */
class FakeReflectionClass
{

    private $fullClassName;
    private $sourceCode;
    public function __construct($fullClassName, $sourceCode)
    {
        $this->fullClassName = $fullClassName;
        $this->sourceCode = $sourceCode;
    }
    public function getName(): string
    {
        return $this->fullClassName;
    }
    public function getShortName(): string
    {
        $strings = explode("\\", $this->fullClassName);
        return end($strings);
    }
    public function getMethods($reflectionMethod): array
    {
        if ($reflectionMethod != \ReflectionMethod::IS_PUBLIC) {
            // throw new NotImplementedException();
        }

        $methods = [];
        $decoration = "public";
        $re = '/^\s*(\/\*\*?[^!][.\s\t\S\n\r]*?\*\/)[.\s\t\S\n\r]*?' . $decoration . ' function ([_a-zA-Z][_a-zA-Z0-9]{0,32})/m';
        preg_match_all($re, $this->sourceCode, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $match) {
            $method = new FakeReflectionMethod($match[2], $match[1]);
            $methods[] = $method;
        }

        return $methods;
    }
}
