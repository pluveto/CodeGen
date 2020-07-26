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