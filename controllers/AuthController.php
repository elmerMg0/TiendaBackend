<?php

namespace app\controllers;

use Exception;
use Yii;

class AuthController extends \yii\web\Controller
{
    public function behaviors() 	
    {     	
    $behaviors = parent::behaviors();  
    //…    
        
    /// add RBAC authorization     	
    $behaviors['access'] = [         	
    'class' => \yii\filters\AccessControl::class,
    'only' => ['actions'], // acciones a las que se aplicará el control
    'except' => ['actions'],	// acciones a las que no se aplicará el control
    'rules' => [
        [
            'allow' => true, // permitido o no permitido
            'actions' => ['acciones'], // acciones que siguen esta regla
            'roles' => ['roles y/o permisos'] // control por roles  permisos
    ],
    [
            'allow' => true, // permitido o no permitido
            'actions' => ['acciones'], // acciones que siguen esta regla
            'matchCallback' => function ($rule, $action) {
    // control personalizado
                                return true;
                            }
    ],
    [
            'allow' => true, // permitido o no permitido
            'actions' => ['acciones'], // acciones que siguen esta regla
            'matchCallback' => function ($rule, $action) {
    // control personalizado equivalente a '@’ de usuario 
    // autenticado
                                return Yii::$app->user->identity ? true : false;
                            }
    ],
        //…
    ],
     ];
    
    //…
        return $behaviors; 	
    }
    

    /* Crear roles y permisos */
    public function actionCreatePermiso(){
        try{
        $auth = Yii::$app->authManager;
        $permission = $auth->createPermission("crearProductos");
        $permission->description = "Crear productos";
        $auth->add ($permission);
        }catch(Exception $e){
            return $e;
        }
    }

    public function actionCreateRol(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $auth = Yii::$app->authManager;
        $role = $auth->createRole($params["nombre"]);
        $auth->add($role);
    }

    public function actionRemovePermiso( $name ){
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission($name);
        $auth->remove($permission);
    }

    public function actionRemoveRole( $name ){
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($name);
        $auth->remove($role);
    }

    public function actionAssignRole($nombre, $idUser){
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($nombre);
        $auth->assign($role, $idUser);
    }  

    public function actionAssignPermission($nombre, $idUser){
        $auth = Yii::$app->authManager;
        $permission = $auth->getpermission($nombre);
        $auth->assign($permission, $idUser);
    }

}
