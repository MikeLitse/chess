<?php 

    class Gateway{

        private PDO $conn;

        public function __construct(Database $database){
            $this->conn=$database->getConnection();
        }

        public function getAllUsers() : array{
            
            $sql = "SELECT * FROM users;";

            $stmt = $this->conn->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC ?: []);//assoc array
        }

        public function getUser(int $id) : array | false{

            $sql = "SELECT * FROM users WHERE id= :id;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);;

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
            if(!empty($new["pass"])){
                $password = password_hash($new["pass"], PASSWORD_DEFAULT) ;
            }else{
                $password = $current["pass"];
            }

            $stmt->bindValue(":user", $new["user"] ?? $current["username"], PDO::PARAM_STR);
            $stmt->bindValue(":pass", $password, PDO::PARAM_STR);
            $stmt->bindValue(":id", $current["id"], PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->rowCount();

        }

        public function createGame(array $data) : array | false{
            
            $sql= null;
            
            if($data["color"] === "white"){
                $sql = "INSERT INTO games ( white, player_turn)
                    VALUES (:idw, :playerturn)";
                $stmt = $this->conn->prepare($sql);

                $stmt->bindValue(":idw", $data["id"],PDO::PARAM_INT);
                $stmt->bindValue(":playerturn", $data["id"], PDO::PARAM_INT);
            }else{
                $sql = "INSERT INTO games (black)
                    VALUES (:idb)";
                $stmt = $this->conn->prepare($sql);

                $stmt->bindValue(":idb", $data["id"],PDO::PARAM_INT);
            }
            
            $stmt->execute();

            $id = $this->conn->lastInsertId();

            return $this->getGame($id);
        }

        public function getGame(int $id): array | false{

            $sql="SELECT * FROM games WHERE id = :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        }

        public function updateGame(int $id, array $data) : int {
            $gamecurr = $this->getGame($id);
            
            if (!$gamecurr) {
                return 0;
            }

            $sql = null;
            
            if (empty($gamecurr["black"])) {
                $sql = "UPDATE games
                        SET black = :userid,
                            status = 'playing'
                        WHERE id = :id";
                
            } elseif (empty($gamecurr["white"])) {
                $sql = "UPDATE games
                        SET white = :userid,
                            status = 'playing'
                        WHERE id = :id";
                
            } else {
                return 0;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":id", $data["id"], PDO::PARAM_INT);
            $stmt->bindValue(":userid", $data["userid"], PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();
        }

    }
?>