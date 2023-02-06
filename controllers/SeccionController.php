<?php

namespace app\controllers;
use Yii;
use app\models\Seccion;
use app\models\Producto;
use yii\web\NotFoundHttpException;

class SeccionController extends \yii\web\Controller
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

    public function actionIndex( $id )
    {
        $seccion = Seccion::findOne( $id );

        if($seccion){
            $products = $seccion->getProductos()->all();
            return [
                "sucess" => true,
                "message" => "La acción se realizo correctamente",
                "seccion" => $seccion,
                "products" => $products
            ];

        }else{
            throw new NotFoundHttpException("No se encontro ninguna seccion");
        }
    }

}
