<?php

namespace app\controllers;
use app\models\User;
use Exception;
use Yii;
class UserController extends \yii\web\Controller
{

    public function behaviors(){
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => [ 'POST' ],

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

    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        try{

            $usuario = new User();
            
            $usuario->nombres = $params["nombre"];
            $usuario->username = $params["username"];
            //$usuario->password = Yii::$app->getSecurity()->encryptByPassword("hey","key");
            $usuario->password_hash = Yii::$app->getSecurity()->generatePasswordHash($params["password"]);
            $usuario->access_token = Yii::$app->security->generateRandomString();
            
            $usuario->save();
        }catch(\Exception $e){
            $usuario = $e;
        }
        return $usuario;
    }

    public function actionLogin()
    {  
        $params = Yii::$app->getRequest()->getBodyParams();
        try{
            $username = isset($params['username']) ? $params['username'] : null;
            $password = isset($params['password']) ? $params['password'] : null;
            
            $user = User::findOne(['username' => $username]);
            if( $user ){
                if(Yii::$app->security->validatePassword($password, $user->password_hash)){
                    $response = [
                        "success" => true,
                        "message" => "Inicio de sesión exitoso",
                        "accessToken" => $user->access_token
                    ];
                    return $response;
                }
            }
            Yii::$app->getResponse()->setStatusCode(400);
            $response = [
                "succes" => false,
                "message" => "Usuario y/o Contraseña incorrecto."
            ];

        }catch(Exception $e){
            Yii::$app->getResponse()->setStatusCode(500);    
            $response = [             
                'success' => false,             	
            'message' => $e->getMessage(),             	
            'code' => $e->getCode(),         	
            ];     
                     
        }
        return $response;
    }
    public function actionLogout()
    {
    
    }
    
}
