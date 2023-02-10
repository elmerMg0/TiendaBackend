<?php

namespace app\controllers;
use app\models\Categoria;

class CategoriaController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $categories = Categoria::find()
                                ->orderBy("id")
                                ->all();
        $response = [
            "success" => true,
            "message" => "La acción se realizo correctamente",
            "categories" => $categories
        ];
        return $response;
    }
    
}
