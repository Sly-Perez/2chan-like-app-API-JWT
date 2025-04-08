<?php

class UserController{

    private UserGateway $gateway;

    public function __construct(UserGateway $gateway){
        $this->gateway = $gateway;
    }

    public function processRequest(string $method, ?string $id, ?string $url): void{
        
        if($id){

            $this->processRequestWithId($method, $id, $url);

        }
        else{
            
            $this->processRequestWithoutId($method, $url);
            
        }

    }

    private function processRequestWithId(string $method, string $id, ?string $url): void{
        
        if($id !== "zero"){
            $user = $this->gateway->getById($id);
    
            if(!$user){
                http_response_code(404);
                echo json_encode([
                    "errors"=>["User with id $id was not found"]
                ]);
                return;
            }
        }

        switch ($method) {
            case "GET":
                if($url === "pictures"){

                    if($id === "zero"){
                        header("Content-Type: image/webp");
                        require_once(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'profilePictures' . DIRECTORY_SEPARATOR . 'blank-profile-picture.webp');
                        return;
                    }

                    $fileName = $user['picture'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    switch ($fileExtension) {
                        case "gif":
                            header("Content-Type: image/gif");
                            break;
                        case "jpeg":
                            header("Content-Type: image/jpeg");
                            break;
                        case "jpg":
                            header("Content-Type: image/jpeg");
                            break;
                        case "png":
                            header("Content-Type: image/png");
                            break;
                        case "webp":
                            header("Content-Type: image/webp");
                            break;
                        default:
                            http_response_code(500);
                            echo json_encode([
                                "errors"=>["Internal Server Error"]
                            ]);
                            return;
                    }
                    
                    $filePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'profilePictures' . DIRECTORY_SEPARATOR . $fileName;
                    
                    if(!file_exists($filePath)){
                        $filePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'page' . DIRECTORY_SEPARATOR . "notFoundOnServer.webp";
                        http_response_code(404);
                        header("Content-Type: image/webp");
                        require_once($filePath);
                        return;
                    }

                    require_once($filePath);
                    return;
                }

                echo json_encode($user);
                break;
            case "DELETE":
                $userDB = $this->getUserByJWT();

                if(!$userDB){
                    http_response_code(401);
                    echo json_encode([
                        "errors"=>["Invalid token"]
                    ]);
                    return;
                }

                //the regular User should have 1 as hierarchyLevelId
                if($userDB["hierarchyLevelId"] === 2){
                    http_response_code(403);
                    return;
                }

                if($userDB["userId"] === intval($id)){
                    http_response_code(403);
                    echo json_encode([
                        "errors"=>["Administrators Cannot delete their own profile"]
                    ]);
                    return;
                }

                if($user["hierarchyLevelId"] !== 2){
                    http_response_code(403);
                    echo json_encode([
                        "errors"=>["Cannot delete other administrators' profiles"]
                    ]);
                    return;
                }

                $this->gateway->deleteById($id);
                echo json_encode([
                    "message"=>["User with $id deleted successfully"]
                ]);

                break;
            default:
                http_response_code(405);
                header("Allow: GET, DELETE");
                break;
        }

    }

    private function processRequestWithoutId(string $method, ?string $url): void{
        switch ($method) {
            case "GET":
                switch ($url) {
                    case "my/profile":
                        //we get user by the sent JWT
                        //it will retrieve ALL their info
                        $userDB = $this->getUserByJWT();

                        if(!$userDB){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["Invalid token"]
                            ]);
                            return;
                        }

                        //we create a user with the information we want to display of the user
                        $user = $this->gateway->getById($userDB["userId"], true);

                        echo json_encode($user);
                        break;
                    default:
                        http_response_code(404);
                        echo json_encode([
                            "errors"=>["Path Not found"]
                        ]);
                        break;
                }
                break;
            case "POST":
                switch ($url) {
                    case "login":
                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);

                        $sanitizedData = $this->getSanitizedInputData($data ?? NULL, NULL);

                        $user = $this->gateway->getByEmail($sanitizedData['email']);

                        if(!$user){
                            http_response_code(401);
                            echo json_encode([
                                "errors"=>["Email or Password Invalid"]
                            ]);
                            return;
                        }

                        if(password_verify($sanitizedData['password'], $user['password'])){
                            
                            $token = $this->generateJWT($user['userId']);
                            $this->setUserTokenByJWT($user, $token);
                            echo json_encode([
                                "message"=>"User logged in successfully",
                                "token"=>$token
                            ]);
                        }
                        else{
                            http_response_code(401);
                            echo json_encode([
                                "errors"=>["Email or Password Invalid"]
                            ]);
                        }

                        break;
                    case "signup":
                        //if a $_POST['jsonBody'] is set, it means the user sent the data as a form-data object. 
                        //Otherwise, it means the user sent the data as a JSON object (with no picture)
                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        $sanitizedData = $this->getSanitizedInputData($data ?? NULL, $_FILES['picture'] ?? NULL);
                        $pictureIsUploaded = false;

                        $errors = $this->getValidationInputErrors($sanitizedData);

                        if(count($errors) !== 0){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>$errors
                            ]);
                            return;
                        }
                        
                        $userDB = $this->gateway->getByEmail($sanitizedData["email"]);
                        
                        if($userDB !== false){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["Email already registered"]
                            ]);
                            return;
                        }
                        
                        
                        if($sanitizedData['picture'] !== NULL){
                            $sanitizedData['picture'] = $this->uploadPicture($sanitizedData['picture']);
                            $pictureIsUploaded = true;
                        }
                        
                        $returnedId = $this->gateway->add($sanitizedData, $pictureIsUploaded);
                        http_response_code(201);
                        $user = $this->gateway->getById($returnedId);
                        echo json_encode($user);
                        
                        break;
                    case "logout":
                        
                        $user = $this->getUserByJWT();

                        if(!$user){
                            http_response_code(401);
                            echo json_encode([
                                "errors"=>["Invalid token"]
                            ]);
                            return;
                        }

                        $this->gateway->unsetUserToken($user);

                        echo json_encode([
                            "message"=>"User logged out successfully"
                        ]);
                        break;
                    case "my/profile":

                        $userDB = $this->getUserByJWT();

                        if(!$userDB){
                            http_response_code(401);
                            echo json_encode([
                                "errors"=>["Invalid token"]
                            ]);
                            return;
                        }

                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        
                        $sanitizedData = $this->getSanitizedInputData($data ?? NULL, $_FILES['picture'] ?? NULL, true);
                        //if any field is NULL, it means the user did not send it. 
                        //Therefore, it will not be validated
                        $errors = $this->getValidationInputErrors($sanitizedData);
                        
                        if(count($errors) !== 0){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>$errors
                            ]);
                            return;
                        }                
                        
                        
                        //in case the user sent a new email to be updated, we will check if another user with the same email already exists
                        if($sanitizedData["email"] !== NULL){

                            $userFound = $this->gateway->getByEmail($sanitizedData["email"]);
                            //we take into account that if it is about the same user (requester), it will not affect anything
                            if( ($userFound !== false) && ($userFound['userId'] !== $userDB['userId']) ){
                                http_response_code(400);
                                echo json_encode([
                                    "errors"=>["Email already registered"]
                                ]);
                                return;
                            }

                        }


                        if($sanitizedData['picture'] !== NULL){
                            $sanitizedData['picture'] = $this->uploadPicture($sanitizedData['picture']);
                            $pictureIsUploaded = true;
                        }
                        
                        $this->gateway->update($userDB, $sanitizedData);
                        
                        $user = $this->gateway->getById($userDB["userId"], true);
                        echo json_encode($user);
                        break;
                    default:
                        http_response_code(404);
                        echo json_encode([
                            "errors"=>["Path Not found"]
                        ]);
                        break;
                }
                break;
            case "DELETE":
                if($url !== "my/profile"){
                    http_response_code(404);
                    echo json_encode([
                        "errors"=>["Path Not found"]
                    ]);
                    return;
                }

                $userDB = $this->getUserByJWT();

                if(!$userDB){
                    http_response_code(401);
                    echo json_encode([
                        "errors"=>["Invalid token"]
                    ]);
                    return;
                }

                if($userDB["hierarchyLevelId"] !== 2){
                    http_response_code(403);
                    echo json_encode([
                        "errors"=>["Administrators Cannot delete their own profile"]
                    ]);
                    return;
                }

                $this->gateway->deleteById($userDB["userId"]);
                echo json_encode([
                    "message"=>["Your profile was deleted successfully"]
                ]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST, DELETE");
                break;
        }
    }

    private function uploadPicture(array $file): string{

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        $currentPath = $file['tmp_name'];

        $newFileName = uniqid('profile-picture-') . '.' . $extension;

        $path = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'profilePictures' . DIRECTORY_SEPARATOR;
        
        $newPath = $path . $newFileName;

        move_uploaded_file($currentPath, $newPath);

        return $newFileName;
    }
    private function containsOnlyAscii(string $string): int | bool{
        return !preg_match('/[^\x00-\x7F]/', $string);
    }

    private function getSanitizedInputData(?array $data, ?array $image, bool $is_updated = false):array{

        $username = htmlspecialchars(trim($data['username'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        $email = htmlspecialchars(trim($data['email'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        $password = htmlspecialchars(trim($data['username'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        
        if($image !== NULL && !empty($image['name']) && ($image['error'] === 0)){
            
            $picture = $image;
            
        }

        $sanitizedData = [];
        
        if($is_updated){
            $sanitizedData['username'] = isset($data['username']) ? $username : NULL;
            $sanitizedData['email'] = isset($data['email']) ? $email : NULL;
            $sanitizedData['password'] = isset($data['password']) ? $password : NULL;
        }
        else{
            $sanitizedData['username'] = $username;
            $sanitizedData['email'] = $email;
            $sanitizedData['password'] = $password;
        }

        $sanitizedData['picture'] = $picture ?? NULL;

        return $sanitizedData;
    }

    private function getValidationInputErrors(array $data, bool $password_will_be_checked = true):array{
        $errors = [];

        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];
        $picture = $data['picture'];

        //username
        if($username !== NULL) {
            if(empty($username)){
                array_push($errors, "The User's username is required");
            }
            else if(is_numeric(substr($username, 0, 1)) || strlen($username) > 30){
                array_push($errors, "The User's username: Cannot start with numbers; Length cannot be greater than 30 characters");
            }
        }

        //email
        if($email !== NULL){
            if(empty($email)){
                array_push($errors, "The User's email is required");
            }
            else if(!(filter_var($email, FILTER_VALIDATE_EMAIL)) || strlen($email) > 100 || !$this->containsOnlyAscii($email)){
                array_push($errors, "The User's email: Must be a valid email; Length cannot be greater than 100 characters; Cannot contain non-latin characters");
            }
        }

        //password
        if($password_will_be_checked && $password !== NULL){
            if(empty($password) || strlen($password) > 30){
                array_push($errors, "The User's password: Cannot be empty; Length cannot be greater than 30 characters");
            }
        }

        //picture
        if($picture !== NULL){
            
            //max size of image will be 2MB
            $max_file_size = 2 * 1024 * 1024; //2MB in bytes

            $allowedTypes = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];
            $allowedExtensions = ['gif', 'jpeg', 'jpg', 'png', 'webp'];
            
            $fileName = basename($picture['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileType = mime_content_type($picture['tmp_name']);
            
            
            //validate file MIME type is allowed
            if(!in_array($fileType, $allowedTypes)){
                array_push($errors, "The User's picture Must be in format: jpeg, jpg, png, gif, webp");
            }
            //validate file extension is allowed (optional)
            else if(!in_array($fileExtension, $allowedExtensions)) {
                array_push($errors, "The User's picture Must be in format: jpeg, jpg, png, gif, webp");
            }
            //validate a max file size
            else if($picture['size'] > $max_file_size){
                array_push($errors, "The User's picture size cannot be more than 2MB");
            }
            //validate the width and height of the image
            else{
                $pictureSize = getimagesize($picture['tmp_name']);
                //$pictureSize[0]->width
                //$pictureSize[1]->height
                if($pictureSize[0] !== $pictureSize[1]){
                    array_push($errors, "The User's picture: Must have the same width and height");
                }
            }
            
        }
        
        return $errors;
    }

    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generateJWT(string $userId):string{

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    
        $uuid = $this->generateUUID();
        $payload = json_encode(['userId' => $userId, 'exp' => time() + 3600, 'jti' => $uuid]);
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
    
        if(!isset($payload['jti'])){
            return false;
        }

        if(!isset($payload['userId']) || !isset($payload['exp'])){
            return false;
        }

        if ($payload['exp'] < time()) {
            return false;
        }
        
        $userDB = $this->gateway->getById($payload['userId'], true);
        
        if(!$userDB){
            return false;
        }
        
        $user = $this->gateway->getByEmail($userDB['email']);

        if(($user['sessionToken'] !== $payload['jti']) || ($user['sessionToken'] === NULL)){
            return false;
        }
    
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'secret-key-that-no-one-knows', true);
        $base64UrlExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
        return ($base64UrlSignature === $base64UrlExpectedSignature);
    }

    private function getEmailByJWT(string $jwt): string | false{
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $jwt);
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        if(!isset($payload['jti'])){
            return false;
        }

        if(!isset($payload['userId']) || !isset($payload['exp'])){
            return false;
        }

        $user = $this->gateway->getById($payload['userId'], true);

        return $user['email'];
    }

    private function setUserTokenByJWT(array $data, string $jwt): void{
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? '';
    
        $token = str_replace('Bearer ', '', $token);

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $jwt);
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        $this->gateway->setUserToken($data, $payload['jti']);
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

    public function getUserByJWT(): array | false{
        $token = $this->getJWT();
        $email = $this->getEmailByJWT($token);
        
        if(!$email){
            return false;
        }

        $user = $this->gateway->getByEmail($email);

        if(!$user){
            return false;
        }

        return $user;
    }
    
}