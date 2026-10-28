<?php 

    class Gateway{

        private PDO $conn;

        public function __construct(Database $database){
            $this->conn=$database->getConnection();
        }

        public function getAllUsers() : array{
            
            $sql = "SELECT * FROM users";

            $stmt = $this->conn->query($sql);
            
            $data=[];

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            
                $data[]= $row;

            }

            return $data;//assoc array
        }

        public function getUser(string $id) : array | false{

            $sql = "SELECT * FROM users WHERE id= :id";

            $stmt = $this->conn->prepare($sql);

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);//returns assoc

            return $data;

        }

    }

?>