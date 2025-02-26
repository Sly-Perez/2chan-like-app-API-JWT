<?php

declare(strict_types = 1);

spl_autoload_register(function($class){
    if($class === 'DbConnection'){
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . "$class.php");
    }
    else if(str_contains($class, "Gateway")){
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'gateway' . DIRECTORY_SEPARATOR . "$class.php");
    }
    else if(str_contains($class, "Controller")){
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . "$class.php");
    }
    else if(str_contains($class, "Handler")){
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'handler' . DIRECTORY_SEPARATOR . "$class.php");
    }
});

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

header("Content-type: application/json; charset=UTF-8");

$parts = explode("/", $_SERVER['REQUEST_URI']);
$url = (($parts[2]==="users" && array_key_exists(3, $parts)) && ($parts[3]==="login" || $parts[3]==="signup" || $parts[3]==="logout")) ? $parts[3] : NULL;
$id = ($url===NULL && array_key_exists(3, $parts)) ? $parts[3] : NULL;


$dbConnection = new DbConnection();
$userGateway = new UserGateway($dbConnection);
$userController = new UserController($userGateway);


$jwtIsValid = $userController->validateJWTMiddleware();

if(($url!=NULL) && ($url!="logout")){

    if($jwtIsValid){
        http_response_code(401);
        echo json_encode([
            "message"=>"Your session has not expired yet"
        ]);
    }
    else{
        $userController->processRequest($_SERVER['REQUEST_METHOD'], $id, $url);
    }

    return;
}

if(!$jwtIsValid){
    http_response_code(401);
    echo json_encode([
        "message"=>"Invalid or expired token"
    ]);
    return;
}

switch ($parts[2]) {
    case "users":
        $gateway = new UserGateway($dbConnection);
        $controller = new UserController($gateway);
        $controller->processRequest($_SERVER['REQUEST_METHOD'], $id, $url);
        break;
}