<?php 

    class Controller{

        public function __construct(private Gateway $gateway){

        }

        public function processRequest(string $method, ?string $id) : void {
            if($id){
                //$this->processResourceRequest($method,$id);
            }else{
                $this->processControllerRequest($method);
            }
        }

        private function processControllerRequest(string $method) : void {
            $user = $this->gateway->getAllUsers();

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "User not found"]);

                return;
            }

            switch ($method){
                case "GET":
                    echo json_encode($user);
                    break;
            }
        }

    }

?>