<?php

namespace app\controllers;

use app\models\Producto;
use Yii;
use yii\data\Pagination;
class ProductoController extends \yii\web\Controller
{
    public function behaviors(){
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => [ 'get' ],
                'create' => [ 'post' ],
                'update' => [ 'put' ],

            ]
         ];
        return $behaviors;
    }

    public function beforeAction( $action ) {
        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $query = Producto::find();
        
        $pagination = new Pagination([
            'defaultPageSize' => 5,

            'totalCount' => $query->count(),
        ]);

        $productos = $query
                    ->offset($pagination->offset)
                    ->limit($pagination->limit)
                    ->all();

        $response = [
            "success" => true,
            "message" => "La acción se realizo corretamente",
            "data"=> $productos,
            [
                
            ]
        ];

        return $response;
    }

}
