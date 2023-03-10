<?php

namespace app\controllers;
use app\models\User;
use Exception;
use Yii;
use yii\web\NotFoundHttpException;

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

    public function actionIndex(){
        $users = User::find()->all();
        $response = [
            "success" => true,
            "message" => "Acción realizada con éxito",
            "users" => $users
        ];
        return $response;
    }

    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        try{

            $usuario = new User();
            
            $usuario->nombres = $params["nombres"];
            $usuario->username = $params["username"];
            //$usuario->password = Yii::$app->getSecurity()->encryptByPassword("hey","key");
            $usuario->password_hash = Yii::$app->getSecurity()->generatePasswordHash($params["password"]);
            //$usuario->access_token = Yii::$app->security->generateRandomString();
            $usuario->access_token = $this->getTokenJwt();
            
            if($usuario->save()){
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "usuario registrado exitosamente",
                    "user" => $usuario
                ];
            }else{
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    "success" => true,
                    "message" => "Existen parametros incorrectos",
                    "user" => $usuario->errors
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

    public function actionLogin()
    {  
        $params = Yii::$app->getRequest()->getBodyParams();
        try{
            $username = isset($params['username']) ? $params['username'] : null;
            $password = isset($params['password']) ? $params['password'] : null;
            
            $user = User::findOne(['username' => $username]);
            if( $user ){
                if(Yii::$app->security->validatePassword($password, $user->password_hash)){
                    $auth = Yii::$app->authManager;
                    $permissions = $auth->getPermissionsByUser($user->id);
                    $response = [
                        "success" => true,
                        "message" => "Inicio de sesión exitoso",
                        "accessToken" => $user->access_token,
                        "permissions" => $permissions
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
    public function getTokenJwt()
    {
        $token = Yii::$app->jwt->getBuilder()
        ->setIssuer('http://example.com')
        ->setAudience('http://example.org')
        ->setId('4f1g23a12aa', true)
        ->setIssuedAt(time())
        ->setExpiration(time() + 3600)
        ->set('uid', 1)
        ->sign(Yii::$app->jwt->getSigner("HS256"), Yii::$app->jwt->key)
        ->getToken();
        return (string) $token;
    
    }
    
}
