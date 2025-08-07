<?php

class VerificationController{

    private UserGateway $userGateway;
    private UserController $userController;

    public function __construct(UserGateway $userGateway, UserController $userController){
        $this->userGateway = $userGateway;
        $this->userController = $userController;
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
        http_response_code(404);
        echo json_encode([
            "errors"=>["Path Not found"]
        ]);
    }

    private function processRequestWithoutId(string $method, ?string $url): void{
        switch ($method) {
            case "POST":
                // /verifications/emails
                switch ($url) {
                    case "emails":
                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        
                        $verificationTokenIsValid = $this->verificationTokenIsValid($data["verificationToken"] ?? "");

                        if(!$verificationTokenIsValid){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["Verification Token is Invalid"]
                            ]);
                            break;
                        }

                        $user = $this->getUserByVerificationToken($data["verificationToken"]);

                        $this->userGateway->activateUser($user["userId"]);

                        http_response_code(200);
                        echo json_encode([
                            "message"=>["Email address verified successfully. Go ahead and Log in!"]
                        ]);
                        break;

                    case "emailResends":

                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        
                        if(!key_exists("userId", $data) || $data["userId"] == null){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["The recipient User Id is required"]
                            ]);
                            return;
                        }

                        if(!key_exists("purpose", $data) || $data["purpose"] == null){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["The purpose of the email to resend is required"]
                            ]);
                            return;
                        }

                        switch ($data["purpose"]) {
                            case 'verifyEmail':
                                $user = $this->userGateway->getInactiveById($data["userId"], true);

                                if(!$user){
                                    http_response_code(404);
                                    echo json_encode([
                                        "errors"=>["To-be-verified User with id {$data["userId"]} was not found"]
                                    ]);
                                    return;
                                }
                                break;
                            case 'changePassword':
                                $user = $this->userGateway->getById($data["userId"], true);

                                if(!$user){
                                    http_response_code(404);
                                    echo json_encode([
                                        "errors"=>["User with id {$data["userId"]} was not found"]
                                    ]);
                                    return;
                                }
                                break;
                            default:
                                http_response_code(400);
                                echo json_encode([
                                    "errors"=>["The purpose of the email can just be: 'verifyEmail', 'changePassword'."]
                                ]);
                                return;
                        }

                        //if (purpose == 'changePassword'), it will be true. Otherwise, false.
                        $is_for_password_change = ($data['purpose'] == 'changePassword');

                        $emailWasSent = $this->userController->sendMail($user, $is_for_password_change);


                        if(!$emailWasSent){
                            http_response_code(500);
                            echo json_encode([
                                "errors"=>["Error while re-sending email. Please try again or come back later."]
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
                        
                        http_response_code(200);
                        echo json_encode([
                            "user"=>$user,
                            "message"=>["Email was re-sent successfully. Please, check your email $emailMasked inbox"]
                        ]);

                        break;
                    case "passwordChanges":
                        //we send {"verificationToken": "example_vf", "password": "example_p"}
                        $data = isset($_POST['jsonBody']) ? json_decode($_POST['jsonBody'], true) : json_decode(file_get_contents("php://input"), true);
                        
                        $verificationTokenIsValid = $this->verificationTokenIsValid($data["verificationToken"] ?? "", true);

                        if(!$verificationTokenIsValid){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>["Verification Token is Invalid"]
                            ]);
                            break;
                        }

                        $userDB = $this->getUserByVerificationToken($data["verificationToken"], true);

                        $sanitizedData = $this->userController->getSanitizedInputData($data ?? NULL, NULL);

                        $errors = $this->userController->getValidationInputErrorsForPassword($sanitizedData);

                        if(count($errors) !== 0){
                            http_response_code(400);
                            echo json_encode([
                                "errors"=>$errors
                            ]);
                            return;
                        }

                        $this->userGateway->updatePasswordById($userDB['userId'], $sanitizedData['password']);

                        $user = $this->userGateway->getById($userDB["userId"], true);
                        
                        $emailWasSent = $this->userController->sendMail($user, false, true);
                        
                        http_response_code(200);
                        echo json_encode([
                            "user"=>$user,
                            "message"=>["Password was changed succesfully."]
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
            
            default:
                http_response_code(405);
                header("Allow: POST");
                break;
        }
    }

    private function verificationTokenIsValid(string $verificationToken, bool $is_for_password_change = false): bool{
        $verifTokenIsAuthentic = $this->validateVerificationToken($verificationToken, $is_for_password_change);

        if(!$verifTokenIsAuthentic){
            return false;
        }

        $user = $this->getUserByVerificationToken($verificationToken, $is_for_password_change);

        if(!$user){
            return false;
        }

        return true;
    }


    private function validateVerificationToken(string $verificationToken, bool $is_for_password_change = false): bool{
        if(!str_contains($verificationToken, '.')){
            return false;
        }

        $parts = explode('.', $verificationToken);
        
        if(count($parts)!=3){
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        if(!isset($payload['userId']) || !isset($payload['exp'])){
            return false;
        }

        if ($payload['exp'] < time()) {
            return false;
        }
        
        if($is_for_password_change){
            $secretKey = 'secret-key-that-no-one-knows-password';
            
            $userDB = $this->userGateway->getById($payload['userId'], true);
        }
        else{
            $secretKey = 'secret-key-that-no-one-knows-email';
            
            $userDB = $this->userGateway->getInactiveById($payload['userId'], true);
        }
        
        if(!$userDB){
            return false;
        }
        
        
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
        $base64UrlExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
        
        return ($base64UrlSignature === $base64UrlExpectedSignature);
    }

    private function getUserByVerificationToken(string $verificationToken, bool $is_for_password_change = false): array | false{
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = explode('.', $verificationToken);
    
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        if(!isset($payload['userId']) || !isset($payload['exp'])){
            return false;
        }

        if($is_for_password_change){
            $user = $this->userGateway->getById($payload['userId'], true);
        }
        else{
            $user = $this->userGateway->getInactiveById($payload['userId'], true);
        }

        return $user;
    }

}