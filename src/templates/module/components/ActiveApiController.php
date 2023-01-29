<?= "<?php\n"; ?>

namespace <?= $name ?>\components;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\IdentityInterface;
use yii\web\Response;

/**
 * This is a base API controller
 */
class ActiveApiController extends ActiveController
{
    /**
     * @var string $modelClass
     */
    public $modelClass = 'yii\base\DynamicModel';

    /**
     * @var IdentityInterface $webUser
     */
    protected $webUser;

    /**
     * @var string $user Component id of yii\web\User
     */
    protected $user;

    /**
     * @var array<string, string> $serializer
     */
    public $serializer = [
        'class' => '<?= $name ?>\components\ApiSerializer',
        'collectionEnvelope' => '_data',
        'metaEnvelope' => '_meta'
    ];

    /**
     * init
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->webUser = $this->module->get($this->user);
    }

    /**
     * behaviors
     *
     * @return array<string, mixed>
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // set only body format and response format
        $behaviors['contentNegotiator']['formats'] = ['application/json' => Response::FORMAT_JSON];

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
        ];

        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [
                HttpBearerAuth::class,
            ],
            'except' => $this->authExcept()
        ];

        return $behaviors;
    }

    /**
     * auth exception list
     *
     * @return string[]
     */
    protected function authExcept(): array
    {
        return ['options'];
    }

    /**
     * get request parameters
     *
     * @return array<string, mixed>
     */
    protected function getRequestParams(): array
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }

        return $requestParams;
    }
}
