<?php
function rglob($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}
/**
 * 将数组导出为字符串，并且进行格式化对齐
 *
 * @param array $expression
 * @return string
 */
function var_export_format(array $expression): string
{
    $export = var_export($expression, TRUE);
    $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
    $array = preg_split("/\r\n|\n|\r/", $export);
    $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
    $export = join(PHP_EOL, array_filter(["["] + $array));
    return $export;
}

/**
 * ======= String exts ======= 
 */

function str_starts_with($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

function str_ends_with($haystack, $needle)
{
    return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

function str_contains($haystack, $needle)
{
    return (strpos($haystack, $needle) !== false);
}

function array_get_if_key_exists($array, $key, $default)
{
    return array_key_exists($key, $array) ? $array[$key] : $default;
}