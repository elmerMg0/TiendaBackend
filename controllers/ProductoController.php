<?php

namespace app\controllers;

use app\models\Marca;
use app\models\Producto;
use app\models\Categoria;
use app\models\ProductoCategoria;
use Yii;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class ProductoController extends \yii\web\Controller
{
    public function behaviors(){
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']];
            
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => [ 'get' ],
                'create' => [ 'post' ],
                'update' => [ 'put'],
                'delete' => [ 'delete' ],
                'Sumproducts' => [ "get" ],
                'Productmaxstock' => [ "get" ],
                'Verifyproductstock' => [ "get" ],
                'Assigncategoria' => [ "put", "post" ],
                'Unssigncategoria' => [ "put", "post" ],

            ]
         ];
        return $behaviors;
    }

    public function beforeAction( $action ) {
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }     
            

        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionIndex($pageSize = 10)
    {
        $query = Producto::find();
        
        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,

            'totalCount' => $query->count(),
        ]);

        $productos = $query
                    ->orderBy("id DESC")
                    ->offset($pagination->offset)
                    ->limit($pagination->limit)
                    ->all();
                    

        $currentPage = $pagination->getPage() + 1;            
        $totalPages = $pagination->getPageCount();
        $response = [
            "success" => true,
            "message" => "La acción se realizo corretamente",
            "pageInfo" => [
                "next" => $currentPage < $totalPages ? $currentPage + 1 : null,
                "previus" => $currentPage == 1 ? null : $currentPage - 1,
                "count" => count($productos),
                "page" => $currentPage,
                "start" => $pagination->getOffset(),
                "pageCount" => $pagination->getPageCount(),
            ],
            "data" => $productos,
        ];

        return $response;
    }

    public function actionUpdate( $id ){
        $product = Producto::findOne($id);
        if($product){

            $params = Yii::$app->getRequest()->getBodyParams();
            $product->load($params,"");
            $product->fecha_actualizacion = Date("Y-m-d H:i:s");
            try{
            if($product->save()){
                $response = [
                    "success" => true,
                    "message" => "El producto se actualizo corretamente.",
                    "product" => $product,
                ];
            }else{
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    "success" => false,
                    "message" => "Algunos campos tienen errores",
                    "errors" => $product->errors,
                ];
            }

            }catch( \Exception $e){
                    Yii::$app->getResponse()->setStatusCode(500);
                    $response = [
                        "success" => false,
                        "message" => "Ocurrio un error al realizar la accion",
                        "error" => $e->getCode()
                    ];
            }

        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Producto no encontrado"
            ];
            //hrow new NotFoundHttpException("Producto no encontrado");
        }
        return $response;

    }
    
    public function actionDelete( $id ){
        $producto = Producto::findOne($id);

        if($producto){
            try{
                $producto->delete();
                $response = [
                    "success" => true,
                    "message" => "producto eliminado correctamente",
                    "data" => $producto
                ];
            }catch(yii\db\IntegrityException $ie){
                Yii::$app->getResponse()->setStatusCode(409, "");
                $response = [
                    "success" => false,
                    "message" =>  "El producto esta siendo usado",
                    "code" => $ie->getCode()
                ];
            }catch(\Exception $e){
                Yii::$app->getResponse()->setStatusCode(422, "");
                $response = [
                    "success" => false,
                    "message" => $e->getMessage(),
                    "code" => $e->getCode()
                ];
            }
        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Producto no encontrado"
            ];
            //throw new NotFoundHttpException("Produto no encontrado");
        }
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();

        $product = new Producto();
        $product -> load( $params, "");
        $product -> fecha_creacion = Date("H-m-d H:i:s");
        try{
            if($product -> save()){
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "El producto se registro correctamente",
                    "data" => $product,
                ];
            }else{
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    "success" => false,
                    "message" => "Existen parametros incorrectos",
                    "errors" => $product->errors,
                ];
            }               
        }catch(\Exception $e){
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                "success" => false,
                "message" => "ocurrio un error al realizar la acción",
                "errors" => $e->getMessage(),
            ];
        }
       
        return $response;
    }

    public function actionSumProducts( $id ){
        $marca = Marca::findOne( $id );
        if($marca){
            $total = Producto::find()
                     ->where(["marca_id" => $id])
                     ->sum("stock");
            return [
                "success" => true,
                "message" => "la suma total de productos de la marca".$marca->nombre,
                "total" => $total,
            ];
        }else{
            throw new NotFoundHttpException("No se encontro ninguna marca");
        }
    } 

    public function actionProductMaxStock(){
        $maxStock = Producto::find()->max("stock");
        $products = Producto::find()->where(["stock" => $maxStock])->all();
        return [
            "success" => true,
            "products" => $products];
    }

    public function actionVerifyProductstock( $id ){
        $producto = Producto::findOne( $id );
        if($producto){
            $hasStock = $producto->stock > 0 ;
            $response =  [
                "success" => true,
                "message" => $hasStock ? "El producto tiene stock" : "El producto no tiene stock",
                    "hasStock" => $hasStock,
                ];
        }else{
            throw new NotFoundHttpException("No se encontro el producto");
        }
        return $response;
    }

    //Asingar una categoria a un producto

    public function actionAssignCategory($producto_id,$categoria_id){
        $producto = Producto::findOne($producto_id);
        if($producto){
            $categoria = Categoria::findOne($categoria_id);
            if($categoria){
                if( !$producto->getCategorias()->where(["id" => $categoria_id])->all() ){

                    $producto->link("categorias",$categoria);
                    $response = [
                        "suceess" => true,
                        "message" => "Producto asignado correctamente a la categoria ".$categoria->nombre
                    ];
                }else{
                    Yii::$app->getResponse()->setStatusCode(422, 'Existing link.');
                    $response = [
                        "success" => false,
                        "message" => "El producto ya pertenece a la categoria ".$categoria->nombre,
                    ];
                }
            }else{
                throw new NotFoundHttpException("Categoria no encontrado");
            }
        }else{
            throw new NotFoundHttpException("Producto no encontrado");
        }
        return $response;
    }

    public function actionUnssignCategory($producto_id, $categoria_id){
        $producto = Producto::findOne($producto_id);
        if($producto){
            $categoria = Categoria::findOne($categoria_id);
            if($categoria){
                if( $producto->getCategorias()->where(["id" => $categoria_id])->all() ){

                    try{
                        $producto->unlink("categorias",$categoria, $delete = true);
                        $response = [
                            "suceess" => true,
                            "message" => "Producto desasignado correctamente de la categoria ".$categoria->nombre
                        ];
                    }catch(yii\base\InvalidCallException $ice){
                        $response = [
                            "success" => false,
                            "message" => $ice->getMessage(),
                            "code" => $ice->getCode(),
                        ];
                    }
                }else{
                    Yii::$app->getResponse()->setStatusCode(422, 'No Existing link.');
                    $response = [
                        "success" => false,
                        "message" => "El producto no pertenece a la categoria ".$categoria->nombre,
                    ];
                }
            }else{
                throw new NotFoundHttpException("Categoria no encontrado");
            }
        }else{
            throw new NotFoundHttpException("Producto no encontrado");
        }
        return $response;
    }

    public function actionGetCategoriesBelongProduct($producto_id){
        $product = Producto::findOne($producto_id);
        if($product){
            $categories = ProductoCategoria::find()
                                            ->select([ "producto_id", "categoria.nombre as nombre_categoria", "categoria_id" ])
                                            ->innerJoin("categoria", "categoria.id = producto_categoria.categoria_id")
                                            ->asArray()
                                            ->where(["producto_id" => $producto_id])
                                            ->all();
            $response = [
                "success" => true,
                "message" => "Las categorias que pertenecen al producto",
                "categories" => $categories
            ];
        }else{

        }
        return $response;
    }
}
