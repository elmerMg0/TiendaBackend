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

    public function actionView( $id )
    {
        $model = Seccion::findOne( $id );

        if($model){
            $seccion = Producto::find()
                        ->select(['producto.*','seccion.codigo AS seccion', "marca.nombre as marca"])
                        ->join("left join", "seccion","seccion.id = producto.seccion_id")
                        ->innerJoin("marca", "marca.id = producto.marca_id")
                        ->asArray()
                        ->all();
            
            return [
                "sucess" => true,
                "message" => "La acción se realizo correctamente",
                "seccion" => $model,
                "products" => $seccion
            ];

        }else{
            throw new NotFoundHttpException("No se encontro ninguna seccion");
        }
    }

}
