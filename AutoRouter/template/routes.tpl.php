<?= "<?php"?>

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

/**
 * Router file.
 * Generated at <?=date("Y-m-d H:i:s")?> by AutoRouter.
 */

use Hyperf\HttpServer\Router\Router;


<?php foreach($this->controllers as $controller): ?>
/**
 * <?php echo $controller->className."\n";?>
 */
<?php foreach($controller->routes as $route): ?>
Router::addRoute('<?=$route->httpMethod;?>','<?=$route->httpRoute;?>', 'App\Controller\<?=$controller->className;?>::<?=$route->functionName;?>');
<?php endforeach;?>

<?php endforeach;?>
