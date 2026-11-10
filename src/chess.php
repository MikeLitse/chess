<?php
    class chess{
        private array $board = [];
        private string $activeColor;
        private string $castling;
        private string $enPassant;
        private int $halfmoveClock;
        private int $fullmoveNumber;

        public function __construct(string $fen){
            $this->loadFromFen($fen);
        }

        public function loadFromFen(string $fen) : void {
            $parts = explode(' ', trim($fen));

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
                    if($square === ""){
                        $emptyCount++;
                    }else{
                        if($emptyCount > 0){
                            $rowStr .= $emptyCount;
                            $emptyCount = 0;
                        }
                        $rowStr .= $square;
                    }
                }

                if($emptyCount > 0){
                    $rowStr .= $emptyCount;
                }

                $fenRows[] = $rowStr;
            }

            $placement = implode("/", $fenRows);

            return sprintf(
                "%s %s %s %s %d %d",
                $placement,
                $this->activeColor,
                $this->castling,
                $this->enPassant,
                $this->halfmoveClock,
                $this->fullmoveNumber
            );
        }
        
        public function move(string $from, string $to) : bool {
            
            [$fromRow, $fromCol] = $this->squareToCoordinates($from);
            [$toRow, $toCol] = $this->squareToCoordinates($to);

            $piece = $this->board[$fromRow][$fromCol];

            if($piece === ""){
                return false;
            }

            if($fromRow === $toRow && $fromCol === $toCol){
                return false;
            }

            $isWhitePiece = ctype_upper($piece);//returns true if its upper

            //check player turn
            if (($this->activeColor === "w" && !$isWhitePiece) || ($this->activeColor === "b" && $isWhitePiece)) {
                return false;
            } 
            
            $targetPiece = $this->board[$toRow][$toCol];

            if ($targetPiece !== "") {
                $isTargetWhite = ctype_upper($targetPiece);
                if ($isWhitePiece === $isTargetWhite) {
                    return false;
                }
            }

            $savedBoard = $this->board;

            $dRow = $toRow - $fromRow;
            $dCol = $toCol - $fromCol;
            $absRow = abs($dRow);// === |$toRow - $fromRow|
            $absCol = abs($dCol);// === |$toCol - $fromCol|

            switch(strtoupper($piece)){
                case "P":
                    $dir = $isWhitePiece ? -1 : 1;
                    $startRow = $isWhitePiece ? 6 : 1;

                    if($dCol === 0 && $dRow === $dir && $targetPiece ===""){
                        break;
                    }

                    if($dCol === 0 && $fromRow === $startRow && $dRow === 2 * $dir){
                        $intermediateRow = $fromRow + $dir;
                        if($this->board[$intermediateRow][$fromCol] === "" && $targetPiece === ""){
                            break;
                        }
                    }

                    if ($absCol === 1 && $dRow === $dir && $targetPiece !== "") {
                        break;
                    }

                    if ($absCol === 1 && $dRow === $dir && $this->enPassant === $to) {
                        $capturedPawnRow = $fromRow;
                        $this->board[$capturedPawnRow][$toCol] = "";
                        break;
                    }

                    return false;

                case "R":
                    if(($dRow !==0 && $dCol !==0) || (!$this->isPathClear($fromRow, $fromCol, $toRow, $toCol))){
                        return false;
                    }
                    break;
                case "N":
                    if(!(($absRow === 1 && $absCol === 2) || ($absRow === 2 && $absCol === 1))){
                        return false;
                    }
                    break;
                case "B":
                    if($absRow !== $absCol || !$this->isPathClear($fromRow, $fromCol, $toRow, $toCol)){
                        return false;
                    }
                    break;
                case "Q":
                    $isDiagonal = ($absRow === $absCol);
                    $isStraight = ($dRow === 0 || $dCol === 0);
                    
                    if((!$isDiagonal && !$isStraight) || !$this->isPathClear($fromRow, $fromCol, $toRow, $toCol)){
                        return false;
                    }
                    break;
                case "K":
                    if($absRow >1 || $absCol > 1){
                        return false;
                    }
                    break;
            }

            $this->board[$toRow][$toCol] = $piece;
            $this->board[$fromRow][$fromCol] = "";

            if($this->isCheck($this->activeColor)){
                $this->board = $savedBoard;
                return false;
            }

            if(strtoupper($piece) === "P" || $targetPiece !== ""){
                $this->halfmoveClock = 0;
            }else{
                $this->halfmoveClock++;
            }

            $this->enPassant = "-";

            if(strtoupper($piece) === "P" && abs($fromRow - $toRow) === 2){
                $skippedRow = ($fromRow + $toRow) / 2;
                $this->enPassant = $this->coordinatesToSquare((int) $skippedRow, $fromCol);
            }

            if ($this->activeColor === 'w') {
                $this->activeColor = 'b';
            } else {
                $this->activeColor = 'w';
                $this->fullmoveNumber++;
            }

            return true;
        } 

        private function squareToCoordinates(string $square): array {
            $file = ord(strtolower($square[0])) - ord('a'); 
            $rank = (int)$square[1];                         
            $row = 8 - $rank;                                

            return [$row, $file];
        }

        private function coordinatesToSquare(int $row, int $col): string {
            $file = chr(ord('a') + $col);
            $rank = 8 - $row;

            return "$file$rank";
        }

        public function getActiveColor(): string {
            return $this->activeColor;
        }

        private function isPathClear(int $fromRow, int $fromCol, int $toRow, int $toCol) : bool{

            $stepRow = $toRow <=> $fromRow;// -1, 0, 1 
            $stepCol = $toCol <=> $fromCol;// -1, 0, 1 

            $currRow = $fromRow + $stepRow;
            $currCol = $fromCol + $stepCol;

            while($currRow !== $toRow || $currCol !== $toCol){
                if($this->board[$currRow][$currCol] !== ""){
                    return false;
                }
                $currRow += $stepRow;
                $currCol += $stepCol;
            }

            return true;

        }

        private function findKing(string $color) : ?array {
            $target = ($color ==="w") ? "K" : "k";

            for($r = 0; $r < 8; $r++){
                for($c = 0; $c < 8; $c++){
                    if($this->board[$r][$c] === $target){
                        return [$r , $c];
                    }
                }
            }

            return null;
        }

        public function isSquareAttacked(int $row, int $col, string $byColor) : bool{

            $knightOffsets = [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]];
            $enemyKnight = ($byColor === "w") ? "N" : "n";

            foreach($knightOffsets as [$dr, $dc]){
                $r = $row + $dr;
                $c = $col + $dc;

                if($r >= 0 && $r < 8 && $c >= 0 && $c < 8 && $this->board[$r][$c] === $enemyKnight){
                    return true;
                }
            }

            $enemyRook = ($byColor === "w") ? "R" : "r";
            $enemyQueen = ($byColor === "w") ? "Q" : "q";

            foreach([[-1,0],[1,0],[0,-1],[0,1]] as [$dr, $dc]){
                $r = $row + $dr;
                $c = $col + $dc;
                $blocked = false;

                while($r >= 0 && $r < 8 && $c >= 0 && $c < 8 && !$blocked){
                    $p = $this->board[$r][$c];
                    if($p !== ""){
                        if($p === $enemyRook || $p === $enemyQueen){
                            return true;
                        }
                        $blocked = true;
                    }
                    $r += $dr;
                    $c += $dc;
                }
            }

            $enemyBishop = ($byColor === "w") ? "B" : "b";

            foreach ([[-1,-1],[-1,1],[1,-1],[1,1]] as [$dr, $dc]){
                $r = $row + $dr;
                $c = $col + $dc;
                $blocked = false;

                while($r >= 0 && $r < 8 && $c >= 0 && $c < 8 && !$blocked){
                    $p = $this->board[$r][$c];
                    if($p !== ""){
                        if($p === $enemyBishop || $p === $enemyQueen){
                            return true;
                        }
                        $blocked = true;
                    }
                    $r += $dr;
                    $c += $dc;
                }
            }

            $pawnRow = ($byColor === "w") ? $row + 1 : $row -1;
            $enemyPawn = ($byColor === "w") ? "P" : "p";

            foreach([-1, 1] as $dc){
                $pawnCol = $col + $dc;

                if($pawnRow >= 0 && $pawnRow < 8 && $pawnCol >= 0 && $pawnCol < 8){
                    if($this->board[$pawnRow][$pawnCol] === $enemyPawn){
                        return true;
                    }
                }
            }

            $enemyKing = ($byColor === "w") ? "K" : "k";

            for($dr = -1; $dr <= 1; $dr++){
                for($dc = -1; $dc <= 1; $dc++){
                    if($dr !== 0 || $dc !== 0){
                        $r = $row + $dr;
                        $c = $col + $dc;

                        if($r >= 0 && $r < 8 && $c >=0 && $c < 8 && $this->board[$r][$c] === $enemyKing){
                            return true;
                        }

                    }
                }
            }

            return false;
        }

        public function isCheck(string $color) : bool{
            
            $kingPos = $this->findKing($color);
            if(!$kingPos){
                return false;
            }

            $opponent = ($color === "w") ? "b" : "w";

            return $this->isSquareAttacked($kingPos[0], $kingPos[1], $opponent);
        }
    }
?>