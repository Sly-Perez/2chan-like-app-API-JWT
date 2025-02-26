<?php

class UserGateway{

    private PDO $dbCon;

    public function __construct(DbConnection $dbConnection){
        $this->dbCon = $dbConnection->connectDB();
    }

    public function getByEmail(string $email): array | false{
        $sql = "SELECT
                    * 
                FROM users 
                WHERE user_email=:user_email";

        $stmt = $this->dbCon->prepare($sql);

        $stmt->bindValue(":user_email", $email, PDO::PARAM_STR);

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }
    //it can return an array (if it exists) or false (if it does not)
    public function getById(string $id): array | false{
        $sql = "SELECT
                    user_id, user_username,
                    user_picture, user_joinedAt,
                    user_amountOfPosts 
                FROM users 
                WHERE user_id=:user_id";

        $stmt = $this->dbCon->prepare($sql);

        $stmt->bindValue(":user_id", $id, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function add(array $data): void{
        $sql = "INSERT INTO 
                    users(user_username, user_email, user_password)
                    VALUES(:user_username, :user_email, :user_password)";

        $stmt = $this->dbCon->prepare($sql);
        
        $stmt->bindValue(":user_username", $data["username"], PDO::PARAM_STR);
        $stmt->bindValue(":user_email", $data["email"], PDO::PARAM_STR);
        $stmt->bindValue(":user_password", password_hash($data["password"], PASSWORD_DEFAULT), PDO::PARAM_STR);

        $stmt->execute();
    }

    public function setUserToken(array $data, string $token):void{
        $sql = "UPDATE users
                    SET user_sessionToken=:user_sessionToken
                    WHERE user_id=:user_id";
        
        $stmt = $this->dbCon->prepare($sql);

        $stmt->bindValue(":user_sessionToken", $token);
        $stmt->bindValue(":user_id", $data['user_id']);

        $stmt->execute();
    }


    public function unsetUserToken(array $data):void{
        $sql = "UPDATE users
                    SET user_sessionToken=:user_sessionToken
                    WHERE user_id=:user_id";
        
        $stmt = $this->dbCon->prepare($sql);

        $stmt->bindValue(":user_sessionToken", NULL);
        $stmt->bindValue(":user_id", $data['user_id']);

        $stmt->execute();
    }

/*
    public function update(array $current, array $new): int{

    }

    public function deleteById(string $id): int{

    }
*/
}