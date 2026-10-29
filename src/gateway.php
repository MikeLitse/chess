<?php 

    class Gateway{

        private PDO $conn;

        public function __construct(Database $database){
            $this->conn=$database->getConnection();
        }

        public function getAllUsers() : array{
            
            $sql = "SELECT * FROM users;";

            $stmt = $this->conn->query($sql);
            
            $data=[];

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            
                $data[]= $row;

            }

            return $data;//assoc array
        }

        public function getUser(string $id) : array | false{

            $sql = "SELECT * FROM users WHERE id= :id;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);//returns assoc

            return $data;

        }

        public function getUserLogin(array $data) : array | false {

            $sql = "SELECT * FROM users WHERE username = :user";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":user", $data["user"], PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If username is not in the DB, stop here
            if (!$user) {
                return false;
            }

            // Verify plain-text password against the database hash
            if (password_verify($data["pass"], $user["pass"])) {
                return $user;
            }

            return false;
        }


        public function createUser(array $data) : string{

            $sql = "INSERT INTO users( username, pass)
                    VALUES (:user, :pass);";

            $stmt = $this->conn->prepare($sql);
            //implement this
            $hash=password_hash($data["pass"], PASSWORD_DEFAULT);
            $stmt->bindValue(":user", $data["user"], PDO::PARAM_STR);
            $stmt->bindValue(":pass", $hash, PDO::PARAM_STR);

            $stmt->execute();

            return $this->conn->lastInsertId();//return last id inserted

        }
        
        public function deleteUser(string $id) : int{

            $sql = "DELETE FROM users WHERE id=:id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->rowCount();//returns affected rows

        }

        public function updateUser(array $current, array $new) : int{
            
            $sql = "UPDATE users
                    SET username = :user, pass = :pass
                    WHERE id = :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":user", $new["user"] ?? $current["user"], PDO::PARAM_STR);
            $stmt->bindValue(":pass", $new["pass"] ?? $current["pass"], PDO::PARAM_STR);
            $stmt->bindValue(":id", $new["id"] ?? $current["id"], PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->rowCount();

        }

    }

?>