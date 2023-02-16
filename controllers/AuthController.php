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
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']];
        /// add RBAC authorization     	
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => [''], // acciones a las que se aplicará el control
            'except' => ['other'],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => [''], // acciones que siguen esta regla
                    'controllers' => ['producto'],
                    'roles' => [''] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['accciones'], // acciones que siguen esta regla
                    'matchCallback' => function ($rule, $action) {
                        // control personalizado
                        return true;
                    }
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => [''], // acciones que siguen esta regla
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


    public function beforeAction( $action ) {
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }     
            

        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /* Crear roles y permisos */
    public function actionCreatePermission()
    {
        try {
            $params = Yii::$app->getRequest()->getBodyParams();
            $auth = Yii::$app->authManager;
            $permission = $auth->createPermission($params["name"]);
            $permission->description = $params["description"];
            $auth->add($permission);
            $response = [
                "success" => true,
                "message" => "Permiso creado exitosamente",
                "data" => $permission
            ];
        } catch (Exception $e) {
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                "success" => false,
                "message" => "Ocurrio un error",
                "error" => $e
            ];
        }
        return $response;
    }

    public function actionCreateRole()
    {
        try {
            $params = Yii::$app->getRequest()->getBodyParams();
            $auth = Yii::$app->authManager;
            $role = $auth->createRole($params["name"]);
            $role->description = ($params["description"]);
            $auth->add($role);
            $response = [
                "success" => true,
                "message" => "Rol creado exitosamente",
                "data" => $role
            ];
        } catch (\Exception $e) {
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                "success" => false,
                "message" => "Ocurrio un error",
                "error" => $e
            ];
        }
        return $response;
    }

    public function actionRemovePermission($name)
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission($name);
        $auth->remove($permission);
    }

    public function actionRemoveRole($name)
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($name);
        $auth->remove($role);
    }

    public function actionAssignRole($nombre, $idUser)
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($nombre);
        if ($role) {
            try {
                $info = $auth->assign($role, $idUser);
                $response = [
                    "success" => true,
                    "message" => "usuario asignado al rol " . $nombre . " exitosamente",
                    "data" => $info
                ];
            } catch (\Exception $e) {
                $response = [
                    "success" => false,
                    "message" => "Usuario ya pertenece al rol " . $nombre,
                    "errors" => $e
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "No existe el rol"
            ];
        }
        return $response;
    }

    public function actionAssignPermission($name, $idUser)
    {
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission($name);
        if ($permission) {
            try {
                $info = $auth->assign($permission, $idUser);
                $response = [
                    "success" => true,
                    "message" => "usuario asignado al permiso " . $name . " exitosamente",
                    "data" => $info
                ];
            } catch (\Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    "success" => false,
                    "message" => "Usuario ya pertenece al permiso " . $name,
                    "errors" => $e
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "No existe el permiso"
            ];
        }
        return $response;
    }
    public function actionAssignPermissionToRole()
    {
        $auth = Yii::$app->authManager;
        $params = Yii::$app->getRequest()->getBodyParams();
        $permission = $auth->getPermission($params["namePermission"]);
        $role = $auth->getRole($params["nameRole"]);
        if ($permission) {
            if ($role) {

                try {

                    $auth->addChild($role, $permission);
                    $response = [
                        "success" => true,
                        "message" => "Permiso asignado a rol exitosamente"
                    ];
                } catch (\Exception $e) {
                    Yii::$app->getResponse()->setStatusCode(500);
                    $response = [
                        "success" => false,
                        "message" => "Ocurrio un error",
                        "error" => $e
                    ];
                }
            } else {
                Yii::$app->getResponse()->setStatusCode(404);
                $response = [
                    "success" => false,
                    "message" => "No existe el rol"
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "No existe el permiso"
            ];
        }
        return $response;
    }

    public function actionGetPermissions(){
        $auth = Yii::$app->authManager;
        $permissions =  $auth->getPermissions();
        $response = [
            "success" => true,
            "message" => "lista de permisos",
            "permissions" => $permissions
        ];
        return $response;
    }

    public function actionGetRoles(){
        $auth = Yii::$app->authManager;
        $roles =  $auth->getRoles();
        $response = [
            "success" => true,
            "message" => "lista de roles",
            "roles" => $roles
        ];
        return $response;
    }

    public function actionGetPermissionByRole($name){
        $auth = Yii::$app->authManager;
        $permissions =  $auth->getPermissionsByRole($name);
        $response = [
            "succes" =>true,
            "message" => "Permisos by role",
            "permissions" => $permissions
        ];
        return $response;
    }

    public function actionGetRolesByUser($useId){
        $auth = Yii::$app->authManager;
        $roles =  $auth->getRolesByUser($useId);
        $response = [
            "succes" =>true,
            "message" => "Roles by user",
            "roles" => $roles
        ];
        return $response;
    }
}


