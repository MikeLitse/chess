<?php
    class chess{
        private array $baord = [];
        private string $activeColor;
        private string $castling;
        private string $enPassant;
        private int $halfmoveClock;
        private int $fullmoveNumber;

        public function __construc(string $fen){
            $this->loadFromFen($fen);
        }

        public function loadFromFen(string $fen) : void {
            $parts = exploed(' ', trim($fen));

            if(count($parts) !== 6){
                
                throw new InvalidArgumentException("Invalid FEN Format");

            }

            [$placement, $color, $castling, $enPassant, $half, $full] = $parts;

            $ranks = explode('/', $placement);
            $this->board = [];

            foreach($ranks as $rankStr){
                $row = [];

                for($i = 0; $i < strlen($rankStr); $i++){
                    $char = $rankStr[$i];

                    if(is_numeric($char)){

                        for($j = 0; $j < (int)$char; $j++){
                            $row[] = '';
                        }

                    }else{
                        $row[] = $char;
                    }
                }
                $this->board[] = $row;
            }

            $this->activeColor = $color;
            $this->castling = $castling;
            $this->enPassant = $enPassant;
            $this->halfmoveClock = (int) $half;
            $this->fullmoveNumber= (int) $full;
        }

        public function toFen() : string {
            $fenRows = [];

            foreach ($this->board as $row){
                
                $rowStr = "";
                $emptyCount = 0;

                foreach ($row as $square){
                    if($square === "")
                }
            }
        } 
    }
?>