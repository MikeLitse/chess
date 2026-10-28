<?php 

    class Controller{

        public function __construct(private Gateway $gateway){

        }

        public function processRequest(string $method, ?string $id) : void {
            if($id){
               $this->processResourceRequest($method,$id);
            }else{
                $this->processControllerRequest($method);
            }
        }

        private function processResourceRequest(string $method, string $id) : void {
            $user = $this->gateway->getUser($id);

            //if there are no users return 404
            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "User Not Found"]);//array that turns into json

                return;
            }

            switch ($method){
                case "GET":
                    echo json_encode($user);
                    break;
            }
        }

        private function processControllerRequest(string $method) : void {
            switch ($method){
                case "GET":
                    echo json_encode($this->gateway->getAllUsers());
                    break;
            }
        }

    }

?>