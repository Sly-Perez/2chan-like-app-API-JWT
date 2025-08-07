<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

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

                        $sanitizedData = $this->getSanitizedInputData($data ?? NULL, NULL, false, true);

                        $user = $this->gateway->getByEmailOrUsername($sanitizedData['emailOrUsername']);

                        if(!$user){
                            http_response_code(401);
                            echo json_encode([
                                "errors"=>["Email/username or Password Invalid"]
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
                                "errors"=>["Email/username or Password Invalid"]
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
                        
                        //see if the username is registered already
                        $userByUsername = $this->gateway->getByUsername($sanitizedData["username"]);
                        
                        if($userByUsername !== false){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["Username already registered"]
                            ]);
                            return;
                        }

                        //see if the email is registered already
                        $userByEmail = $this->gateway->getByEmail($sanitizedData["email"]);
                        
                        if($userByEmail !== false){
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
                        
                        $user = $this->gateway->getInactiveById($returnedId, true);
                        

                        $emailWasSent = $this->sendMail($user);


                        if(!$emailWasSent){
                            http_response_code(500);
                            echo json_encode([
                                "errors"=>["Error while creating account. Please try again or come back later."]
                            ]);
                            return;
                        }

                        //we'll split the user email
                        $emailSplit = explode("@", $user["email"]);
                        
                        $localPart = $emailSplit[0];
                        $domainPart = "@" . $emailSplit[1];
                        //$localPart => example, $emailSplit => @gmail.com
                        
                        //we show the two first characters of the user email, asterisks to fill the rest and the domain of the user email
                        //ex . **** . @gmail.com
                        // ex*****@gmail.com
                        $emailMasked = substr($localPart, 0, 2) . str_repeat("*", strlen($localPart) - 2) . $domainPart;
                        
                        http_response_code(201);
                        echo json_encode([
                            "user"=>$user,
                            "message"=>["Account created. Please, check your inbox to verify your email address $emailMasked"]
                        ]);
                        
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
                        
                        
                        //in case the user sent a new username to be updated, we will check if another user with the same username already exists
                        if($sanitizedData["username"] !== NULL){

                            $userFoundByUsername = $this->gateway->getByUsername($sanitizedData["username"]);
                            //we take into account that if it is about the same user (requester), it will not affect anything
                            if( ($userFoundByUsername !== false) && ($userFoundByUsername['userId'] !== $userDB['userId']) ){
                                http_response_code(400);
                                echo json_encode([
                                    "errors"=>["Username already registered"]
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
                    case "my/password":
                        //user needs to send their email in the body
                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        
                        $sanitizedData = $this->getSanitizedInputData($data ?? NULL, NULL);

                        $errors = $this->getValidationInputErrorsForEmail($sanitizedData);

                        if(count($errors) !== 0){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>$errors
                            ]);
                            return;
                        }

                        $user = $this->gateway->getByEmail($sanitizedData['email']);
                        
                        if(!$user){
                            http_response_code(404);
                            echo json_encode([
                                "errors"=>["Email does not exist"]
                            ]);
                            return;
                        }

                        $emailWasSent = $this->sendMail($user, true);

                        if(!$emailWasSent){
                            http_response_code(500);
                            echo json_encode([
                                "errors"=>["An error occured. Please try again or come back later."]
                            ]);
                            return;
                        }

                        //we'll split the user email
                        $emailSplit = explode("@", $user["email"]);
                        
                        $localPart = $emailSplit[0];
                        $domainPart = "@" . $emailSplit[1];
                        //$localPart => example, $emailSplit => @gmail.com
                        
                        //we show the three first characters of the user email, asterisks to fill the rest and the domain of the user email
                        //ex . **** . @gmail.com
                        // ex*****@gmail.com
                        $emailMasked = substr($localPart, 0, 2) . str_repeat("*", strlen($localPart) - 2) . $domainPart;
                        
                        http_response_code(200);
                        echo json_encode([
                            "user"=>$user,
                            "message"=>["We sent you an email to $emailMasked with the instructions to change your password"]
                        ]);

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

    public function getSanitizedInputData(?array $data, ?array $image, bool $is_updated = false, bool $is_for_login = false):array{

        $sanitizedData = [];

        if($is_for_login){
            $emailOrUsername = htmlspecialchars(trim($data['emailOrUsername'] ?? ""), ENT_NOQUOTES, 'UTF-8');

            $sanitizedData['emailOrUsername'] = $emailOrUsername;
        }

        $username = htmlspecialchars(trim($data['username'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        $email = htmlspecialchars(trim($data['email'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        $password = htmlspecialchars(trim($data['password'] ?? ""), ENT_NOQUOTES, 'UTF-8');
        
        if($image !== NULL && !empty($image['name']) && ($image['error'] === 0)){
            
            $picture = $image;
            
        }
        
        if($is_updated){
            $sanitizedData['username'] = isset($data['username']) ? $username : NULL;
            $sanitizedData['email'] = NULL;
            $sanitizedData['password'] = NULL;
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

    private function getValidationInputErrorsForEmail(array $data):array{
        $errors = [];

        $email = $data['email'];
        
        //email
        if($email !== NULL){
            if(empty($email)){
                array_push($errors, "The User's email is required");
            }
            else if(!(filter_var($email, FILTER_VALIDATE_EMAIL))){
                array_push($errors, "The User's email: Must be a valid email;");
            }
        }
        
        return $errors;
    }
    public function getValidationInputErrorsForPassword(array $data):array{
        $errors = [];

        $password = $data['password'];
        
        //password
        if(empty($password) || strlen($password) > 30){
            array_push($errors, "The User's password: Cannot be empty; Length cannot be greater than 30 characters");
        }
    
        return $errors;
    }

    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generateJWT(string $userId, bool $is_for_email_verification = false, bool $is_for_password_change = false):string{

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        if($is_for_email_verification){
            $secretKey = 'secret-key-that-no-one-knows-email';
            //it expires in 2 hours
            $payload = json_encode(['userId' => $userId, 'exp' => time() + 7200]);
        }
        else if($is_for_password_change){
            $secretKey = 'secret-key-that-no-one-knows-password';
            //it expires in 15 minutes
            $payload = json_encode(['userId' => $userId, 'exp' => time() + 900]);
        }
        else{
            $secretKey = 'secret-key-that-no-one-knows';
            $uuid = $this->generateUUID();
            $payload = json_encode(['userId' => $userId, 'exp' => time() + 3600, 'jti' => $uuid]);
        }

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
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

    //send email of confirmation
    public function sendMail(array $user, bool $is_for_password_change = false, bool $is_for_notification_password_change = false): bool{

        $mail = new PHPMailer(true);
        $HTMLTemplates = new HTMLTemplates();
        $NonHTMLTemplates = new NonHTMLTemplates();

        
        try {
            //Server settings
            
            //(JUST FOR DEV)
            //enable debug of email sending process:
            //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
            //(JUST FOR DEV)
            $mail->SMTPDebug = 0;
            
            $mail->isSMTP();                                           //Send using SMTP
            
            $mail->Host       = 'smtp-host';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'sender-email-address';                     //SMTP username
            $mail->Password   = 'sender-email-password';                               //SMTP password
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            /* (ALTERNATIVE) for when SMTP fails
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            (ALTERNATIVE) for when SMTP fails */
            
            /*  (JUST FOR DEV)*/
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            /*(JUST FOR DEV) */
            
            //Recipients
            $mail->setFrom('sender-email-address', 'Weekie Mochi');
            $mail->addAddress($user["email"], $user["username"]);
            
            
            //We create a token
            if($is_for_password_change){
                $verificationToken = $this->generateJWT($user["userId"], false, true);
                
                $emailSubject = 'Instructions - Change your password';
                $emailVerificationHTML = $HTMLTemplates->getConfirmPasswordChangeTemplate($user, $verificationToken);
                $emailVerificationNonHTML = $NonHTMLTemplates->getConfirmPasswordChangeTemplate($user, $verificationToken);
            }
            else if($is_for_notification_password_change){
                $emailSubject = 'Changes - Your password was reset';
                $emailVerificationHTML = $HTMLTemplates->getPasswordChangeNotificationTemplate($user);
                $emailVerificationNonHTML = $NonHTMLTemplates->getPasswordChangeNotificationTemplate($user);
            }
            else{
                $verificationToken = $this->generateJWT($user["userId"], true);
                $emailSubject = 'Email Verification - New Account';
                $emailVerificationHTML = $HTMLTemplates->getVerificationEmailTemplate($user, $verificationToken);
                $emailVerificationNonHTML = $NonHTMLTemplates->getVerificationEmailTemplate($user, $verificationToken);
            }

            //Content
            $mail->isHTML(true);
            $mail->Subject = $emailSubject;
            $mail->Body = $emailVerificationHTML;
            $mail->AltBody = $emailVerificationNonHTML;

            return $mail->send();
        } catch (\Throwable $th) {
            
            return false;
        }

    }
    
}