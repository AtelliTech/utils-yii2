<?php

namespace v1\controllers;

use v1\components\ActiveApiController;
use yii\data\ActiveDataProvider;

/**
 * @OA\Tag(
 *     name="{className}",
 *     description="Everything about your {className}",
 * )
 *
 * @OA\Get(
 *     path="{apiPath}",
 *     summary="List all {className}",
 *     description="List all {className}",
 *     operationId="list{className}",
 *     tags={"{className}"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         @OA\Schema(ref="#/components/schemas/StandardParams/properties/page")
 *     ),
 *     @OA\Parameter(
 *         name="pageSize",
 *         in="query",
 *         @OA\Schema(ref="#/components/schemas/StandardParams/properties/pageSize")
 *     ),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         @OA\Schema(ref="#/components/schemas/StandardParams/properties/sort")
 *     ),
 *     @OA\Parameter(
 *         name="fields",
 *         in="query",
 *         @OA\Schema(ref="#/components/schemas/StandardParams/properties/fields")
 *     ),
 *     @OA\Parameter(
 *         name="expand",
 *         in="query",
 *         @OA\Schema(type="string", enum={"xxxx"}, description="Query related models, using comma(,) be seperator")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/{className}")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="{apiPath}/{id}",
 *     summary="Get {className} by particular id",
 *     description="Get {className} by particular id",
 *     operationId="get{className}",
 *     tags={"{className}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="{className} id",
 *         required=true,
 *         @OA\Schema(ref="#/components/schemas/{className}/properties/id")
 *     ),
 *     @OA\Parameter(
 *         name="fields",
 *         in="query",
 *         @OA\Schema(ref="#/components/schemas/StandardParams/properties/fields")
 *     ),
 *     @OA\Parameter(
 *         name="expand",
 *         in="query",
 *         @OA\Schema(type="string", enum={"xxxx"}, description="Query related models, using comma(,) be seperator")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(type="object", ref="#/components/schemas/{className}")
 *     )
 * )
 *
 * @OA\Post(
 *     path="{apiPath}",
 *     summary="Create a record of {className}",
 *     description="Create a record of {className}",
 *     operationId="create{className}",
 *     tags={"{className}"},
 *     @OA\RequestBody(
 *         description="{className} object that needs to be added",
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
{annotations}
 *             )
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(type="object", ref="#/components/schemas/{className}")
 *     )
 * )
 *
 * @OA\Patch(
 *     path="{apiPath}/{id}",
 *     summary="Update a record of {className}",
 *     description="Update a record of {className}",
 *     operationId="update{className}",
 *     tags={"{className}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="{className} id",
 *         required=true,
 *         @OA\Schema(ref="#/components/schemas/{className}/properties/id")
 *     ),
 *     @OA\RequestBody(
 *         description="{className} object that needs to be updated",
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
{annotations}
 *             )
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(type="object", ref="#/components/schemas/{className}")
 *     )
 * )
 *
 * @OA\Delete(
 *     path="{apiPath}/{id}",
 *     summary="Delete a record of {className}",
 *     description="Delete a record of {className}",
 *     operationId="delete{className}",
 *     tags={"{className}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="{className} id",
 *         required=true,
 *         @OA\Schema(ref="#/components/schemas/{className}/properties/id")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     )
 * )
 *
 * @version 1.0.0
 */
class {className}Controller extends ActiveApiController
{
    /**
     * @var string $modelClass
     */
    public $modelClass = 'app\models\{className}';

    /**
     * {@inherit}
     */
    public function actions()
    {
        $actions = parent::actions();

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['dataFilter'] = [
            'class' => 'yii\data\ActiveDataFilter',
            'searchModel' => $this->modelClass
        ];

        $actions['index']['pagination'] = [
            'class' => 'v1\components\Pagination'
        ];

        return $actions;
    }

    /**
     * @OA\Post(
     *     path="{apiPath}/search",
     *     summary="Search {className} by particular params",
     *     description="Search {className}",
     *     operationId="search{className}",
     *     tags={"{className}"},
     *     @OA\RequestBody(
     *         description="search {className}",
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                  @OA\Property(property="page", ref="#/components/schemas/StandardParams/properties/page"),
     *                  @OA\Property(property="pageSize", ref="#/components/schemas/StandardParams/properties/pageSize"),
     *                  @OA\Property(property="sort", ref="#/components/schemas/StandardParams/properties/sort"),
     *                  @OA\Property(property="fields", ref="#/components/schemas/StandardParams/properties/fields"),
     *                  @OA\Property(property="expand", type="string", enum={"xxxx"}, description="Query related models, using comma(,) be seperator"),
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/{className}")
     *         )
     *     )
     * )
     *
     * Search {className}
     */
    public function actionSearch()
    {
        try {
            $params = $this->getRequestParams();

            // prepare query
            $query = call_user_func([$this->modelClass, 'find']);

            // check params
            if (!empty($typeIds) && is_array($typeIds)) {
                // $query->andWhere(['type_id'=>$typeIds]);
            }

            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'class' => 'v1\components\Pagination',
                    'params' => $params
                ],
                'sort' => [
                    'enableMultiSort' => true,
                    'params' => $params
                ]
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
