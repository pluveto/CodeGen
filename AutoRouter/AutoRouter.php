<?php

namespace Pluveto\CodeGen\AutoRouter;

use Exception;

use Pluveto\CodeGen\AutoRouter\RouteParser;

/**
 * This class generates router class automatically by calling `generateRouter()`
 * 调用此类的 `generateRouter()` 方法可自动生成路由和权限文件
 * @author Pluveto <i@pluvet.com>
 * @copyright 2020 Incolore Team
 */
class AutoRouter
{
    private static $instance;
    public static function getInstance()
    {
        return self::$instance ? self::$instance : self::$instance = new self();
    }

    private string $routerFileName;
    private string $routerTemplateFileName;
    private string $ruleFileName;
    private string $ruleTemplateFileName;
    private string $scanPattern;

    private string $apiFileName;
    private string $apiTemplateFileName;

    public function __construct()
    {
        $config = require_once(realpath(__DIR__ . "/../config.php"));

        $this->routerFileName           = $config["routerFileName"];
        $this->routerTemplateFileName   = $config["routerTemplateFileName"];
        $this->ruleFileName             = $config["ruleFileName"];
        $this->ruleTemplateFileName     = $config["ruleTemplateFileName"];
        $this->apiFileName              = $config["apiFileName"];
        $this->apiTemplateFileName      = $config["apiTemplateFileName"];
        $this->scanPattern              = $config["scanPattern"];
        

        if (
            is_file($this->routerTemplateFileName) &&
            is_file($this->ruleTemplateFileName)
        ) {
        } else {
            echo "Missing template.\n";
            die();
        }

        // echo "routerFileName: " . $this->routerFileName . "\n";
        // echo "routerTemplateFileName: " . $this->routerTemplateFileName . "\n";
        // echo "ruleFileName: " . $this->ruleFileName . "\n";
        // echo "ruleTemplateFileName: " . $this->ruleTemplateFileName . "\n";
    }
    /**元素结构
     * controller {
     *   className
     *   routes{
     *      httpMethod  
     *      httpRoute   
     *      functionName
     *   }
     * }
     */
    private array $controllers;
    private array $apiRules;
    private array $apiPerms;
    public function generateRouter()
    {

        $filesToScan = rglob($this->scanPattern);
        $classesMeta = $this->getControllerClasses($filesToScan);

        [$this->controllers, $this->apiRules, $this->apiPerms] = $this->analyzeController($classesMeta);

        // Router
        ob_start();
        require($this->routerTemplateFileName);
        $result = ob_get_clean();
        file_put_contents($this->routerFileName, $result);
        echo "\nRoutes generated: " . $this->routerFileName . "\n\n";

        // Rule
        ob_start();
        require($this->ruleTemplateFileName);
        $result = ob_get_clean();
        file_put_contents($this->ruleFileName, $result);
        echo "\nRules generated: " . $this->ruleFileName . "\n\n";
    }

    public function generateApi()
    {

        if(!is_file($this->apiTemplateFileName)){
            throw new Exception("Missing Api Template File Name");
        }

        $filesToScan = rglob($this->scanPattern);
        $classesMeta = $this->getControllerClasses($filesToScan);

        [$this->controllers, $this->apiRules, $this->apiPerms] = $this->analyzeController($classesMeta);
        $oldFileContent = file_get_contents($this->apiFileName);
        ob_start();
        require($this->apiTemplateFileName);
        $result = ob_get_clean();
        if(!trim($oldFileContent)){
            file_put_contents($this->apiFileName, $result);
        }else{
            $re = "/(api = {)([.\s\t\S\n\r]*)}(\/\/api)/";
            file_put_contents($this->apiFileName, preg_replace($re,"$1".$result."$3", $oldFileContent));
        }
        
        echo "\nApi generated: " . $this->apiFileName . "\n\n";
    }


    /**
     * @param \ReflectionClass[] classesMeta
     */
    private function analyzeController($classesMeta)
    {
        // Things to return
        $controllers = [];
        $apiRuleList = [];
        $apiPerms = [];

        foreach ($classesMeta as $classMeta) {
            $classFullName = $classMeta->getName();
            echo "Parsing class: \e[1;36m$classFullName\e[0m\n";
            $controller = new \stdClass;
            $controller->className = $className = $classMeta->getShortName();
            $controller->routes = [];
            foreach ($classMeta->getMethods(\ReflectionMethod::IS_PUBLIC) as $function) {
                $functionName = $function->getName();
                if (str_starts_with($functionName, "__")) continue;

                $comment = $function->getDocComment();
                [$httpMethod, $httpRoute, $apiPermission, $apiRule, $apiName, $apiVersion, $apiEncrypted] = $this->extractAnnotation($comment);
                $httpRoute = "/v" . $apiVersion[0] . rtrim($httpRoute, "/");
                if (null == $httpMethod) {
                    echo "--->Skip $functionName\n";
                    continue;
                }
                echo "---> Found API: \e[1;35m" . str_pad($functionName, 18) . "\e[0m ";
                if (is_array($apiRule) && count($apiRule)) {
                    $apiRuleList["$httpRoute"] = $apiRule;
                }
                if ($apiPermission != "none") $apiPerms[] = ["name" => $apiName, "value" => $apiPermission];
                $apiRuleList["$httpRoute"]['__permission'] = $apiPermission;
                $apiRuleList["$httpRoute"]['__encrypted'] = $apiEncrypted;                
                echo "\e[1;32m" . str_pad("[$httpMethod]", 8) . "$httpRoute\e[0m\n";

                $route = new \stdClass;
                $route->httpMethod      = $httpMethod;
                $route->httpRoute       = $httpRoute;
                $route->functionName    = $functionName;

                $controller->routes[] = $route;
            }
            $controllers[] = $controller;
        }

        return [
            $controllers,
            $apiRuleList,
            $apiPerms
        ];
    }
    /**
     * Is a file name a legal controller.
     *
     * @param string $fileName
     * @return boolean
     */
    private function isControllerFileName($fileName)
    {
        return !str_starts_with($fileName, "Base")
            && !str_starts_with($fileName, "Abstract")
            &&  str_ends_with($fileName, "Controller");
    }

    /**
     * Cast file path (relative) to class name
     *
     * @param string $filePath
     * @return void
     */
    private function filePathToClassFullName($filePath)
    {
        $filePath = str_replace("/", "\\", $filePath); // turn `App/Controller/IndexController.php` to `App\Controller\IndexController.php`
        $className = "\\" . substr($filePath, 0, (strrpos($filePath, "."))); // turn `App\Controller\IndexController.php` to `\App\Controller\IndexController`
        return $className;
    }

    /**
     * Select controller meta from files
     *
     * @param array<string> $filesToScan
     * @return void
     */
    private function getControllerClasses($filesToScan)
    {

        $ret = [];
        foreach ($filesToScan as $filePath) {
            $pathInfo = pathinfo($filePath);
            if (!$this->isControllerFileName($pathInfo['filename'])) {
                continue;
            }
            echo "Found file: $filePath\n";

            $classFullName = $this->filePathToClassFullName($filePath);
            $sourceCode = file_get_contents($filePath);
            $ret[] = new FakeReflectionClass($classFullName, $sourceCode);
        }
        return $ret;
    }
    /**
     * 解析 API 参数规则
     * 
     * @param string $str str without `@apiParam `
     * @return array
     */
    private function getRule($str) //str without `@apiParam `
    {
        /**
         * 魔法阵
         */
        $re =
            '/^\s*(?:\(\s*(.+?)\s*\)\s' .
            '*)?\s*(?:\{\s*([a-zA-Z0-9' .
            '()#:\.\/\\\\\[\]_|-]+)\s*' .
            '(?:\{\s*(.+?)\s*\}\s*)?\s' .
            '*(?:=\s*(.+?)(?=\s*\}\s*)' .
            ')?\s*\}\s*)?(\[?\s*([a-zA' .
            '-Z0-9\$\:\.\/\\\\_-]+(?:\[' .
            '[a-zA-Z0-9\.\/\\\\_-]*\])' .
            '?)(?:\s*=\s*(?:"([^"]*)"|' .
            '\'([^\']*)\'|(.*?)(?:\s|' .
            '\]|$)))?\s*\]?\s*)(.*)?$|@/';

        preg_match($re, $str, $matches, PREG_OFFSET_CAPTURE, 0);

        /**
         * 返回的树
         */
        $retBody = [];

        /**
         * --> 参数的名称
         */
        $paramName = $matches[6][0];

        /**
         * --> 参数的描述
         */
        $description = trim($matches[10][0]) ? trim($matches[10][0]) : $paramName;
        $retBody["description"] = $description;
        /**
         * --> type 字段
         */
        $type = "string";
        if ($matches[2][0] && (gettype($matches[2][0]) == "string")) {
            if (substr($type, -strlen($type)) === "[]") {
                $type = "array";
            } else {
                $type = $matches[2][0];
            }
        }
        $in  = ["number",  "object"];
        $out = ["integer", "string"];
        $type = str_replace($in, $out, strtolower($type));

        $retBody["type"] = $type;

        /**
         * --> min/max 字段
         */

        $sizeMin = -1;
        $sizeMax = -1;
        if ($matches[3][0] && (gettype($matches[3][0]) == "string")) {
            [$sizeMin, $sizeMax] = explode($type === "string" ? ".." : "-", $matches[3][0], 2);
        }
        if ($sizeMin != -1)    $retBody["min"] = intval($sizeMin);
        if ($sizeMax != -1)    $retBody["max"] = intval($sizeMax);

        /**
         * --> options 字段
         */

        $options = [];
        $optionsStr = $matches[4][0];
        if ($optionsStr && (gettype($optionsStr) == "string")) {
            $regExp = "";
            if ($optionsStr[0] === '"')
                $regExp = '/\"[^\"]*[^\"]\"/';
            else if ($optionsStr[0] === '\'')
                $regExp = '/\'[^\']*[^\']\'/';
            else
                $regExp = '/[^,\s]+/';
            preg_match_all($regExp, $optionsStr, $options);
        }
        if (count($options))
            $retBody["options"] = $options[0];

        /**
         * --> required 字段
         */

        if (!($matches[5][0] && $matches[5][0][0] === '[')) {
            $retBody["required"] = true;
        }

        /**
         * --> default 字段
         */

        $default = null;
        if ($matches[7][0]) $default = $matches[7][0];
        elseif ($matches[8][0]) $default = $matches[8][0];
        elseif ($matches[9][0]) $default = $matches[9][0];

        if ($default) {
            if ($type == "integer") $default = intval($default);
            if ($type == "boolean"){                
                $default = json_decode($default);
            };
            $retBody["default"] = $default;
        } else {
            if ($type == "string" && !$retBody["required"]) $retBody["default"] = '';
        }

        /**
         * 完事儿
         */

        return [
            $paramName,
            $retBody
        ];
    }

    /**
     * 注释解析
     *
     * @param string $comment
     * @return array
     */
    function extractAnnotation($comment)
    {
        if (!str_contains($comment, "@api ")) {
            return [null, null, null, null, null, null, null];
        }

        $httpMethod = "";
        $httpRoute = "";
        $apiPermission = "none";
        $apiVersion = "";
        $apiEncrypted = false;
        $apiRule = [];
        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            // @api {get} / 欢迎界面
            $line = ltrim($line, " \t/*");
            if (strlen($line) == 0) continue;
            if (strpos($line, "@api ") === 0) {
                $line = trim(substr($line, strlen("@api ")));
                $blocks = explode("}", $line, 2); // get, / 欢迎界面            
                $httpMethod = strtoupper(substr($blocks[0], 1));
                $blocks[1] = ltrim($blocks[1], " "); /// 欢迎界面
                $subblock = explode(" ", $blocks[1], 2); ///,欢迎界面
                $httpRoute = trim($subblock[0]);
                $apiName = trim($subblock[1]);
            }
            if (strpos($line, "@apiPermission ") === 0) {
                $line = substr($line, strlen("@apiPermission ")); // user
                $apiPermission = trim($line);
            }
            if (strpos($line, "@apiEncrypted") === 0) {
                $apiEncrypted = true;
            }
            if (strpos($line, "@apiVersion ") === 0) {
                $line = substr($line, strlen("@apiVersion ")); // user
                $apiVersion = explode(".", trim($line));
            }
            if (strpos($line, "@apiParam ") === 0) {
                $line = substr($line, strlen("@apiParam ")); // user
                $rule = $this->getRule($line);
                $apiRule[$rule[0]] = $rule[1];
            }
        }
        if (trim($httpRoute) === '') {
            echo "\n****** Failed to find route !! ******";
            die();
        }
        return [$httpMethod, $httpRoute, $apiPermission, $apiRule, $apiName, $apiVersion,  $apiEncrypted];
    }

    /**
     * Get the value of apiFileName
     */
    public function getApiFileName()
    {
        return $this->apiFileName;
    }

    /**
     * Set the value of apiFileName
     *
     * @return  self
     */
    public function setApiFileName($apiFileName)
    {
        $this->apiFileName = $apiFileName;

        return $this;
    }

    /**
     * Get the value of apiTemplateFileName
     */
    public function getApiTemplateFileName()
    {
        return $this->apiTemplateFileName;
    }

    /**
     * Set the value of apiTemplateFileName
     *
     * @return  self
     */
    public function setApiTemplateFileName($apiTemplateFileName)
    {
        $this->apiTemplateFileName = $apiTemplateFileName;

        return $this;
    }
}
