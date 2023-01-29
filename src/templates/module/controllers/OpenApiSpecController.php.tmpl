<?= "<?php\n"; ?>

namespace <?= $name ?>\controllers;

use Exception;
use OpenApi\Annotations as OA;
use Yii;
use yii\web\HttpException;

/**
 * @OA\OpenApi(
 *     security={{"Bearer": {}}},
 *     @OA\Server(
 *         url="https://api.xxx.com",
 *         description="[Dev] APIs server"
 *     ),
 *     @OA\Info(
 *         version="1.0.0",
 *         title="API<?= $name ?>",
 *         description="APIs document of <?= $name ?> that based on Swagger OpenAPI",
 *         termsOfService="http://swagger.io/terms/",
 *         @OA\Contact(name="xxxx"),
 *         @OA\License(name="MIT", identifier="MIT")
 *     ),
 * )
 *
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Bearer {Access Token}",
 *     securityScheme="Bearer"
 * )
 */
class OpenApiSpecController extends \yii\web\Controller
{
    /**
     * display swagger yaml
     *
     * @param string $format default: yaml
     */
    public function actionIndex(string $format = null): void
    {
        $cache = $this->module->get('cache');
        $cacheKey = 'cache.apidoc.' . $format;
        $modulePath = Yii::getAlias('<?= $nameAlias ?>');
        $modelPath = Yii::getAlias('@app/models');
        $openapi = \OpenApi\Generator::scan([$modulePath, $modelPath]);
        $contents = $cache->get($cacheKey);
        if ($format == 'json') {
            if (empty($contents))
                $contents = $openapi->toJson();

            $this->response->format = 'json';
        } elseif ($format == 'yaml') {
            if (empty($contents))
                $contents = $openapi->toYaml();
            $this->response->headers->set('Content-Type', 'application/x-yaml');
        } else {
            if (empty($contents)) {
                $viewFile = Yii::getAlias('<?= $nameAlias ?>/views/view_apidoc.php');
                $yamlUri = strstr(\yii\helpers\Url::to(['/apidoc', 'format'=>'yaml'], true), '//');
                $contents = $this->view->renderFile($viewFile, ['yamlUri'=>$yamlUri]);
            }
            $this->response->format = 'html';
        }

        // set cache
        $cache->set($cacheKey, $contents, 120);

        // response
        $this->response->content = $contents;
        $this->end();
    }
}
