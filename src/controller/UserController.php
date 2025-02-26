<?php

class UserController{

    private UserGateway $gateway;

    public function __construct(UserGateway $gateway){
        $this->gateway = $gateway;
    }

    public function processRequest(string $method, ?string $id, ?string $url): void{
        
        if($id){

            $this->processResourceRequest($method, $id);

        }
        else{
            
            $this->processCollectionRequest($method, $url);
            
        }

    }

    private function processResourceRequest(string $method, string $id): void{
        $user = $this->gateway->getById($id);

        if(!$user){
            http_response_code(404);
            echo json_encode(["error"=>"User with id $id was not found"]);
            return;
        }

        switch ($method) {
            case "GET":
                echo json_encode($user);
                break;
            
            default:
                http_response_code(405);
                header("Allow: GET, PATCH");
                break;
        }

    }

    private function processCollectionRequest(string $method, ?string $url): void{
        switch ($method) {
            case "POST":
                
                switch ($url) {
                    case "login":
                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        $sanitizedData = $this->getSanitizedInputData($data);

                        $user = $this->gateway->getByEmail($sanitizedData['email']);

                        if(!$user){
                            http_response_code(401);
                            echo json_encode([
                                "error"=>"Email or Password Invalid"
                            ]);
                            return;
                        }

                        if(password_verify($sanitizedData['password'], $user['user_password'])){
                            http_response_code(200);
                            $token = $this->generateJWT($user['user_email']);
                            $this->gateway->setUserToken($user, $token);
                            echo json_encode([
                                "message"=>"User logged in successfully",
                                "token"=>$token
                            ]);
                        }
                        else{
                            http_response_code(401);
                            echo json_encode([
                                "error"=>"Email or Password Invalid"
                            ]);
                        }

                        break;
                    case "signup":
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $sanitizedData = $this->getSanitizedInputData($data);

                        $errors = $this->getValidationInputErrors($sanitizedData);

                        if(count($errors) === 0){
                            $user = $this->gateway->getByEmail($sanitizedData["email"]);

                            if(!$user){
                                
                                $this->gateway->add($sanitizedData);
                                http_response_code(201);
                                echo json_encode($sanitizedData);
                                return;
                                
                            }

                            http_response_code(400);
                            echo json_encode([
                                "error"=>"Email already registered"
                            ]);
                        }
                        else{
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>$errors
                            ]);
                        }
                        break;
                    case "logout":
                        $token = $this->getJWT();
                        $email = $this->deconstructJWT($token);
                        
                        if(!$email){
                            http_response_code(400);
                            echo json_encode([
                                "error"=>"Invalid token"
                            ]);
                            return;
                        }

                        $user = $this->gateway->getByEmail($email);

                        if($user != false){
                            $this->gateway->unsetUserToken($user);

                            echo json_encode([
                                "message"=>"User logged out successfully"
                            ]);
                        }
                        
                        break;
                }
                break;
            default:
                http_response_code(405);
                header("Allow: POST");
                break;
        }
    }

    private function getSanitizedInputData(array $data):array{

        $user_username = filter_var(trim($data['username'] ?? ""), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $user_languagePreference = filter_var(trim($data['languagePreference'] ?? "EN"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $user_email = filter_var(trim($data['email'] ?? ""), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $user_password = filter_var(trim($data['password'] ?? ""), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        

        $data['username'] = $user_username;
        $data['languagePreference'] = $user_languagePreference;
        $data['email'] = $user_email;
        $data['password'] = $user_password;

        return $data;
    }

    private function getValidationInputErrors(array $data, bool $is_new = true):array{
        $errors = [];

        $user_username = $data['username'];
        $user_email = $data['email'];
        $user_password = $data['password'];
        $user_languagePreference = $data['languagePreference'];

        //user_username
        if(empty($user_username)){
            array_push($errors, "The User's username is required");    
        }
        else if(is_numeric(substr($user_username, 0, 1)) || strlen($user_username) > 30){
            array_push($errors, "The User's username: Cannot start with numbers; Length cannot be greater than 30 characters");
        }

        //user_email
        if(empty($user_email)){
            array_push($errors, "The User's email is required");
        }
        else if(!(filter_var($user_email, FILTER_VALIDATE_EMAIL)) || strlen($user_email) > 100){
            array_push($errors, "The User's email: Must be a valid email; Length cannot be greater than 100 characters");
        }

        //user_password
        if($is_new){
            if(empty($user_password) || strlen($user_password) > 30){
                array_push($errors, "The User's password: Cannot be empty; Length cannot be greater than 30 characters");
            }
        }

        //user_picture
        //feature not allowed yet

        //user_languagePreference
        if($user_languagePreference != 'EN' && $user_languagePreference != 'ES' && $user_languagePreference != 'ZH'){
            array_push($errors, "The User's Language Preference: Can just be English, Spanish or Chinese"); 
        }

        return $errors;
    }

    private function generateJWT(string $email):string{

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    
        $payload = json_encode(['email' => $email, 'exp' => time() + 3600]);
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'secret-key-that-no-one-knows', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }

    public function validateJWT(string $jwt):bool{
        if(!str_contains($jwt, '.')){
            return false;
        }

        $parts = explode('.', $jwt);
        
        if(count($parts)!=3){
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);
    
        if(!isset($payload['exp']) || !isset($payload['email'])){
            return false;
        }

        if ($payload['exp'] < time()) {
            return false;
        }
        
        $user = $this->gateway->getByEmail($payload['email']);

        if(!$user){
            return false;
        }

        if(($user['user_sessionToken']!==$jwt) || ($user['user_sessionToken']===NULL)){
            return false;
        }
    
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'secret-key-that-no-one-knows', true);
        $base64UrlExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
        return ($base64UrlSignature === $base64UrlExpectedSignature);
    }

    private function deconstructJWT(string $jwt): string | false{
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $jwt);
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        if(!isset($payload['exp']) || !isset($payload['email'])){
            return false;
        }

        return $payload['email'];
    }

    public function getJWT():string{
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? '';
    
        $token = str_replace('Bearer ', '', $token);
    
        return $token;
    }
    
    public function validateJWTMiddleware():bool {
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? '';
    
        if (!$token) {
            return false;
        }
    
        $token = str_replace('Bearer ', '', $token);
    
        if (!$this->validateJWT($token)) {
            return false;
        }

        return true;
    }
    
}