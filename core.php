<?php

    require_once "vendor/autoload.php";
    use Firebase\JWT\JWT;

    class Errors {
        
        public function columnsData ($conexion,$table,$columns) {
            $dbName = $this->get_dbname ();
            $validar = $conexion->query ("SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = '$dbName' AND table_name = '$table'")
            ->fetchAll(PDO::FETCH_OBJ);
            
            $sum = 0;
            
            if (empty($validar)) {
                return null;
            } else {
                
                
                if ($columns[0] == "*") {
                    array_shift($columns);
                }
                
                foreach ($validar as $key => $value) {
                    $sum += in_array($value->item,$columns);
                }
                
                return $sum == count($columns) ? $validar : null;
                
            }
        }
        
    }

    class Get extends Errors {
        
        public function getData ( $tabla , $select , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $selectArray = explode (",",$select);
            
            if ( empty($lab->columnsData ($conexion,$tabla,$selectArray))) {
                echo json_encode (["error" => "404 not found."]);
                return;
            }
            
            $sql = '';
            
            if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                
                $sql = "SELECT $select FROM $tabla ORDER  BY $orderby $ordermode";
                
            } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                
            } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla LIMIT $startat , $endat";
                
            } else {
                
                $sql = "SELECT $select FROM $tabla";
                
            }
            
            try {
                
                $stmt = $conexion -> prepare ( $sql );
            
                if ( $stmt -> execute () ) {
                    return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                }
                
            } catch (Exception $e) {
                die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
            }
            
            echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
        }
        
        public function getDataFilter ( $tabla , $select , $key , $value , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $valores = explode ( "|" , $value );
            $campos = explode ( "|" , $key );
            $selectArray = explode (",",$select);
            
            foreach ($campos as $key => $value) {
                array_push ($selectArray,$value);
            }
            
            $selectArray = array_unique ($selectArray);
            
            if ( empty($lab->columnsData ($conexion,$tabla,$selectArray))) {
                echo json_encode (["error" => "404 not found."]);
                die();
            }
            
            $query = '';
            
            if ( count ( $campos ) > 0 ) {
                
                foreach ( $campos as $key => $value ) {
                    
                    if ( $key > 0 ) {
                        
                        $query .= "AND " . $value . " = :" . $value . " ";
                        
                    }
                    
                }
                
            }
            
            $sql = '';
            
            if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." = :".$campos[0]." $query ORDER  BY $orderby $ordermode";
                
            } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." = :".$campos[0]." $query ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                
            } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." = :".$campos[0]." $query LIMIT $startat , $endat";
                
            } else {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." = :".$campos[0]." $query";
                
            }
            
            $stmt = $conexion -> prepare ( $sql );
            
            foreach ( $campos as $key => $value ) {
                    
                $stmt -> bindParam ( ":$value" , $valores[$key] , PDO::PARAM_STR );
                    
            }
            
            if ( $stmt -> execute () ) {
                return $stmt -> fetchAll ( PDO::FETCH_CLASS );
            }
            
            echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
        }
        
        public function getTablesData ( $rel , $type , $select , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $relArray = explode ( '|' , $rel );
            $typeArray = explode ( '|' , $type );
            
            $innerJoinText = '';
            
            if ( count ( $relArray ) > 1 ) {
                
                foreach ( $relArray as $key => $value ) {
                    
                    if ( empty($lab->columnsData ($conexion,$value,["*"]))) {
                        echo json_encode (["error" => "404 not found."]);
                        return;
                    }
                    
                    if ( $key > 0 ) {
                        
                        $innerJoinText .= "INNER JOIN " . $value . " ON " . $relArray[0] . ".id_" . $typeArray[$key] . "_" . $typeArray[0] . " = " . $value . ".id_". $typeArray[$key] . " ";
                        
                    }
                    
                }
            
                 $sql = '';
                
                if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText ORDER  BY $orderby $ordermode";
                    
                } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                    
                } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText LIMIT $startat , $endat";
                    
                } else {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText";
                    
                }
                
                try {
                    $stmt = $conexion -> prepare ( $sql );
                    if ( $stmt -> execute () ) {
                        return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                    }
                } catch (Exception $e) {
                    die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
                }
                
                echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
            } else {
                echo json_encode ( ["error" => "404 not found."] );
            }
            
        }
        
        public function getTablesDataFilter ( $rel , $type , $select , $key , $value , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $campos = explode ( "|" , $key );
            
            $valores = explode ( "|" , $value );
            
            $query = '';
            
            if ( count ( $campos ) > 0 ) {
                
                foreach ( $campos as $key => $value ) {
                    
                    if ( $key > 0 ) {
                        
                        $query .= "AND " . $value . " = :" . $value . " ";
                        
                    }
                    
                }
                
            }
            
            $relArray = explode ( '|' , $rel );
            $typeArray = explode ( '|' , $type );
            $innerJoinText = '';
            
            if ( count ( $relArray ) > 1 ) {
                
                foreach ( $relArray as $key => $value ) {
                    
                    if ( empty($lab->columnsData ($conexion,$value,["*"]))) {
                        echo json_encode (["error" => "404 not found."]);
                        return;
                    }
                    
                    if ( $key > 0 ) {
                        
                        $innerJoinText .= "INNER JOIN " . $value . " ON " . $relArray[0] . ".id_" . $typeArray[$key] . "_" . $typeArray[0] . " = " . $value . ".id_". $typeArray[$key] . " ";
                        
                    }
                    
                }
            
                 $sql = '';
                
                if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $campos[0] = :$campos[0] $query ORDER  BY $orderby $ordermode";
                    
                } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $campos[0] = :$campos[0] $query ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                    
                } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $campos[0] = :$campos[0] $query LIMIT $startat , $endat";
                    
                } else {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $campos[0] = :$campos[0] $query";
                    
                }
                
                try {
                    $stmt = $conexion -> prepare ( $sql );
                    
                    foreach ( $campos as $key => $value ) {
                    
                        $stmt -> bindParam ( ":$value" , $valores[$key] , PDO::PARAM_STR );
                            
                    }
                
                    if ( $stmt -> execute () ) {
                        return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                    }
                } catch (Exception $e) {
                    die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
                }
                
                echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
            } else {
                echo json_encode ( [
                    "error" => "necesitas mas de una tabla para crear una relacion entre tablas."    
                ]);
            }
            
        }
        
        public function getDataSearch ( $tabla , $select , $key , $search , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $campos = explode ( "|" , $key );
            
            $searchArray = explode ( "|" , $search );
            
            $selectArray = explode (",",$select);
            
            foreach ($campos as $key => $value) {
                array_push ($selectArray,$value);
            }
            
            $selectArray = array_unique ($selectArray);
            
            if ( empty($lab->columnsData ($conexion,$tabla,$selectArray))) {
                echo json_encode (["error" => "404 not found."]);
                die();
            }
            
            $query = '';
            
            if ( count ( $campos ) > 0 ) {
                
                foreach ( $campos as $key => $value ) {
                    
                    if ( $key > 0 ) {
                        
                        $query .= "AND " . $value . " = :" . $value . " ";
                        
                    }
                    
                }
                
            }
            
            $sql = '';
            
            if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query ORDER  BY $orderby $ordermode";
                
            } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                
            } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query LIMIT $startat , $endat";
                
            } else {
                
                $sql = "SELECT $select FROM $tabla WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query";
                
            }
            
            try {
                $stmt = $conexion -> prepare ( $sql );
                
                foreach ( $campos as $key => $value ) {
                    
                    if ( $key > 0 ) {
                    
                        $stmt -> bindParam ( ":$value" , $searchArray[$key] , PDO::PARAM_STR );
                    
                    }
                        
                }
            
                if ( $stmt -> execute () ) {
                    return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                }
            } catch (Exception $e) {
                die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
            }
            
           
            echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
        }
        
        public function getTablesDataSearch ( $rel , $type , $select , $key , $search , $orderby , $ordermode , $startat , $endat , $conexion , $lab ) {
            
            $campos = explode ( "|" , $key );
            
            $searchArray = explode ( "|" , $search );
            
            $query = '';
            
            if ( count ( $campos ) > 0 ) {
                
                foreach ( $campos as $key => $value ) {
                    
                    if ( $key > 0 ) {
                        
                        $query .= "AND " . $value . " = :" . $value . " ";
                        
                    }
                    
                }
                
            }
            
            $relArray = explode ( '|' , $rel );
            $typeArray = explode ( '|' , $type );
            $innerJoinText = '';
            
            if ( count ( $relArray ) > 1 ) {
                
                foreach ( $relArray as $key => $value ) {
                    
                     if ( empty($lab->columnsData ($conexion,$value,["*"]))) {
                        echo json_encode (["error" => "404 not found."]);
                        return;
                    }
                    
                    if ( $key > 0 ) {
                        
                        $innerJoinText .= "INNER JOIN " . $value . " ON " . $relArray[0] . ".id_" . $typeArray[$key] . "_" . $typeArray[0] . " = " . $value . ".id_". $typeArray[$key] . " ";
                        
                    }
                    
                }
            
                 $sql = '';
                
                if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query ORDER  BY $orderby $ordermode";
                    
                } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                    
                } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query LIMIT $startat , $endat";
                    
                } else {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE ".$campos[0]." LIKE '%".$searchArray[0]."%' $query";
                    
                }
                
                try {
                    $stmt = $conexion -> prepare ( $sql );
                    
                    foreach ( $campos as $key => $value ) {
                    
                        if ( $key > 0 ) {
                        
                            $stmt -> bindParam ( ":$value" , $searchArray[$key] , PDO::PARAM_STR );
                        
                        }
                            
                    }
                
                    if ( $stmt -> execute () ) {
                        return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                    }
                } catch (Exception $e) {
                    die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
                }
                
                echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            
            } else {
                echo json_encode ( [
                    "error" => "necesitas mas de una tabla para crear una relacion entre tablas."    
                ]);
            }
            
        }
        
        public function getDataRange ( $tabla , $select , $key , $betweenstart , $betweenend , $orderby , $ordermode , $startat , $endat , $filter , $in , $conexion , $lab ) {
            
            $keyLinkToArray = explode ("|",$key);
            if ($filter != null) {
                $filterArray = explode ("|",$filter);
            }else{
                $filterArray = [];
            }
            $selectArray = explode (",",$select);
            
            foreach ($keyLinkToArray as $key => $value) {
                array_push ($selectArray,$value);
            }
            
            foreach ($filterArray as $key => $value) {
                array_push ($selectArray,$value);
            }
            
            $selectArray = array_unique ($selectArray);
            
            if ( empty($lab->columnsData ($conexion,$tabla,$selectArray))) {
                echo json_encode (["error" => "404 not found."]);
                die();
            }
            
            $filterString = '';
            
            if ( $filter != null && $in != null ) {
                
                $filterString = "AND $filter IN ($in)";
                
            }
            
            $sql = '';
            
            if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE $key BETWEEN '$betweenstart' AND '$betweenend' $filterString ORDER  BY $orderby $ordermode";
                
            } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE $key BETWEEN '$betweenstart' AND '$betweenend' $filterString ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                
            } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                
                $sql = "SELECT $select FROM $tabla WHERE $key BETWEEN '$betweenstart' AND '$betweenend' $filterString LIMIT $startat , $endat";
                
            } else {
                
                $sql = "SELECT $select FROM $tabla WHERE $key BETWEEN '$betweenstart' AND '$betweenend' $filterString";
                
            }
            
            try {
                $stmt = $conexion -> prepare ( $sql );
            
                if ( $stmt -> execute () ) {
                    return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                }
            } catch (Exception $e) {
                die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
            }
            
            if ( empty($stmt -> fetchAll ( PDO::FETCH_CLASS )) ){
                echo json_encode ( ["error" => "404 not found."] );
            } else {
                echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
            }
            
        }
        
        public function getTablesDataRange ( $rel , $type , $select , $linkTo , $betweenstart , $betweenend , $orderby , $ordermode , $startat , $endat , $filter , $in , $conexion , $lab ) {
            
            $filterString = '';
            
            if ( $filter != null && $in != null ) {
                
                $filterString = "AND $filter IN ($in)";
                
            }
            
            $relArray = explode ( '|' , $rel );
            $typeArray = explode ( '|' , $type );
            $innerJoinText = '';
            
            if ( count ( $relArray ) > 1 ) {
                
                foreach ( $relArray as $key => $value ) {
                    
                    if ( empty($lab->columnsData ($conexion,$value,["*"]))) {
                        echo json_encode (["error" => "404 not found."]);
                        return;
                    }
                    
                    if ( $key > 0 ) {
                        
                        $innerJoinText .= "INNER JOIN " . $value . " ON " . $relArray[0] . ".id_" . $typeArray[$key] . "_" . $typeArray[0] . " = " . $value . ".id_". $typeArray[$key] . " ";
                        
                    }
                    
                }
            
                $sql = '';
                
                
                if ( $orderby != null && $ordermode != null && $startat == null && $endat == null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $linkTo BETWEEN '$betweenstart' AND '$betweenend' $filterString ORDER  BY $orderby $ordermode";
                    
                } else if ( $orderby != null && $ordermode != null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $linkTo BETWEEN '$betweenstart' AND '$betweenend' $filterString ORDER  BY $orderby $ordermode LIMIT $startat , $endat";
                    
                } else if ( $orderby == null && $ordermode == null && $startat != null && $endat != null ) {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $linkTo BETWEEN '$betweenstart' AND '$betweenend' $filterString LIMIT $startat , $endat";
                    
                } else {
                    
                    $sql = "SELECT $select FROM ".$relArray[0]." $innerJoinText WHERE $linkTo BETWEEN '$betweenstart' AND '$betweenend' $filterString";
                    
                }
                
                try {
                    $stmt = $conexion -> prepare ( $sql );
                
                    if ( $stmt -> execute () ) {
                        return $stmt -> fetchAll ( PDO::FETCH_CLASS );
                    }
                } catch (Exception $e) {
                    die ("algo no esta bien con la peticion <a href='https://api.adaptowebs.com'>try again</a>");
                }
                
                if ( empty($stmt -> fetchAll ( PDO::FETCH_CLASS )) ) {
                    echo json_encode ( ["error" => "404 not found."] );
                } else {
                    echo json_encode ( $stmt -> fetchAll ( PDO::FETCH_CLASS ) );
                }
                
            } else {
                echo json_encode ( ["error" => "404 not found."] );
            }
            
        }
        
    }
    
    class Post extends Get {
        
        public function postData ( $tabla , $data , $conexion ) {
            
            $columnNames = '';
            $columnValues = '';
            
            foreach ($data['columns'] as $key => $value) {
                
                $columnNames .= $value . ",";
                $columnValues .= ":" . $value . ",";
                
            }
            
            $columnNames = substr($columnNames , 0 , -1);
            $columnValues = substr($columnValues , 0 , -1);
            
            $sql = "INSERT INTO $tabla ($columnNames) VALUES ($columnValues)";
            
            $stmt = $conexion -> prepare ( $sql );
            
            foreach ( $data['columns'] as $key => $value ) {
                    
                $stmt -> bindParam ( ":$value" , $data['values'][$key] , PDO::PARAM_STR );
                    
            }
            
            if ( $stmt -> execute () ) {
                
                echo json_encode ( [
                    "success" => "Operacion realizada con exito.",
                    "ultimo_insertado" => $conexion->lastInsertId()
                    ] , http_response_code (200));
                    
                    return true;
                
            } else {
                
                echo json_encode ( ["fail" => "Operacion realizada, Fallo."] , http_response_code (404));
                
                return false;
                
            }
            
        }
        
    }
    
    class Put extends Post {
        
        public function putData ($tabla,$data,$conexion,$lab) {
            
            if ( isset ($_GET['id']) && isset( $_GET['column'] ) ) {
                
                $id = htmlspecialchars ( $_GET['id'] );
                        
                $column = htmlspecialchars ( $_GET['column'] );
                
                $response = $lab -> getDataFilter ( $tabla , $column , $column , $id , null , null, null , null , $conexion , $lab );
                
                if ( empty($response) ) {
                            
                    echo json_encode ( 
                        [
                            "ERROR" => "El Registro no Existe."
                        ] , http_response_code (404)
                    );
                    die();
                }
                
                $columns = [];
                
                foreach ( array_keys ($data) as $key => $value ) {
                    
                    array_push ($columns,$value);
                    
                }
                
                array_push ($columns,$column);
                
                $columns = array_unique ($columns);
                
                if ( empty (
                        $lab -> columnsData ($conexion,$tabla,$columns)
                        )
                    ) {
                        
                        echo json_encode ( [
                            "ERROR" => "404 Las Columnas Del Formulario No Coinciden"    
                        ] , http_response_code ( 404 ) );
                        
                    } else {
                        
                        $set = '';
                        
                        foreach ($data as $key => $value) {
                
                            $set .= " " . $key . " = :" . $key . ",";
                            
                        }
                        
                        $set = substr($set , 0 , -1);
                        
                        $sql = "UPDATE $tabla SET ".$set." WHERE ".$column." = :".$column;
                        
                        $stmt = $conexion -> prepare ( $sql );
            
                        foreach ( $data as $key => $value ) {
                                
                            $stmt -> bindParam ( ":".$key , $data[$key] , PDO::PARAM_STR );
                                
                        }
                        
                        $stmt -> bindParam ( ":".$column , $id , PDO::PARAM_STR );
                        
                        if ( $stmt -> execute () ) {
                            
                            echo json_encode ( [
                                "success" => "Operacion UPDATE realizada con exito."
                                ] , http_response_code (200));
                            
                        } else {
                            
                            echo json_encode ( ["fail" => "Operacion UPDATE , Fail."] , http_response_code (404));
                            
                        }
                        
                    }
                
            } else {
                
                $response = $lab -> getDataFilter ( $data['tabla'] , $data['column'] , $data['column'] , $data[$data['column']] , null , null, null , null , $conexion , $lab );
                
                if ( empty($response) ) {
                            
                    echo json_encode ( 
                        [
                            "ERROR" => "El Registro no Existe."
                        ] , http_response_code (404)
                    );
                    die();
                }
                
                $columns = [];
                
                $dataToken = array_slice($data,2);
                
                foreach ( array_keys ($dataToken) as $key => $value ) {
                    
                    array_push ($columns,$value);
                    
                }
                
                $columns = array_unique ($columns);
                
                if ( empty (
                        $lab -> columnsData ($conexion,$tabla,$columns)
                        )
                    ) {
                        
                        echo json_encode ( [
                            "ERROR" => "404 Las Columnas Del Formulario No Coinciden"    
                        ] , http_response_code ( 404 ) );
                        
                    } else {
                        
                        $set = '';
                        
                        foreach ($dataToken as $key => $value) {
                
                            $set .= " " . $key . " = :" . $key . ",";
                            
                        }
                        
                        $set = substr($set , 0 , -1);
                        
                        $sql = "UPDATE $tabla SET ".$set." WHERE id_".sufijo()." = :id_".sufijo();
                        
                        $stmt = $conexion -> prepare ( $sql );
            
                        foreach ( $dataToken as $key => $value ) {
                                
                            $stmt -> bindParam ( ":".$key , $dataToken[$key] , PDO::PARAM_STR );
                                
                        }
                        
                        $stmt -> bindParam ( ":id_".sufijo() , $dataToken['id_'.sufijo()] , PDO::PARAM_STR );
                        
                        if ( $stmt -> execute () ) {
                            
                            echo json_encode ( [
                                "success" => "Operacion UPDATE realizada con exito."
                                ] , http_response_code (200));
                            
                        } else {
                            
                            echo json_encode ( ["fail" => "Operacion UPDATE , Fail."] , http_response_code (404));
                            
                        }
                        
                    }
                
            }
            
        }
        
    }
    
    class Delete extends Put {
        
        public function deleteData ($tabla,$conexion,$lab) {
            
            if ( isset ($_GET['id']) ) {
                
                $id = htmlspecialchars ( $_GET['id'] );
                        
                $column = htmlspecialchars ( $_GET['column'] );
                
                $columns = [$column];
                
                $response = $lab -> getDataFilter ( $tabla , $column , $column , $id , null , null, null , null , $conexion , $lab );
                
                if ( empty($response) ) {
                            
                    echo json_encode ( 
                        [
                            "ERROR" => "El Registro no Existe."
                        ] , http_response_code (404)
                    );
                    die();
                }
                
                if ( empty (
                        $lab -> columnsData ($conexion,$tabla,$columns)
                        )
                    ) {
                        
                        echo json_encode ( [
                            "ERROR" => "404 Las Columnas Del Formulario No Coinciden"    
                        ] , http_response_code ( 404 ) );
                        
                } else {
                    
                    $sql = "DELETE FROM $tabla WHERE $column = :$column";
                    
                    $stmt = $conexion -> prepare ( $sql );
                    
                    $stmt -> bindParam ( ":".$column , $id , PDO::PARAM_STR );
                    
                    if ( $stmt -> execute () ) {
                        
                        echo json_encode ( [
                            "success" => "Operacion DELETE realizada con exito."
                            ] , http_response_code (200));
                        
                    } else {
                        
                        echo json_encode ( ["fail" => "Operacion DELETE , Fail."] , http_response_code (404));
                        
                    }
                        
                }
                
            }
            
        }
        
    }

    class Element extends Delete {
        
        public static function get ($tag,$txt,$attributes='') {
            return "<$tag $attributes>$txt</$tag>";
        }
        
    }
    
    class Content extends Element {

        private $content = '';

        public function setContent ( $content ) {
            $this->content = $content;
        }

        public function getContent () {
            return $this->content;
        }
        
        public function build ($head,$body,$lang="en",$attributes='') {
            echo '<!DOCTYPE html>
            <html lang="'.$lang.'">
                <head>
                    <meta charset="UTF-8">
                    <meta name="description" content="Free Web tutorials">
                    <meta name="keywords" content="HTML, CSS, JavaScript">
                    <meta name="author" content="Ramiro Garcia Gonzalez">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Protest+Riot&display=swap" rel="stylesheet">
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Protest+Strike&display=swap" rel="stylesheet">
                    <link rel="icon" type="image/x-icon" href="logo.png">
                    <style>
                        /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */

                        /* Document
                           ========================================================================== */
                        
                        /**
                         * 1. Correct the line height in all browsers.
                         * 2. Prevent adjustments of font size after orientation changes in iOS.
                         */
                        
                        html {
                          line-height: 1.15; /* 1 */
                          -webkit-text-size-adjust: 100%; /* 2 */
                        }
                        
                        /* Sections
                           ========================================================================== */
                        
                        /**
                         * Remove the margin in all browsers.
                         */
                        
                        body {
                          margin: 0;
                        }
                        
                        /**
                         * Render the `main` element consistently in IE.
                         */
                        
                        main {
                          display: block;
                        }
                        
                        /**
                         * Correct the font size and margin on `h1` elements within `section` and
                         * `article` contexts in Chrome, Firefox, and Safari.
                         */
                        
                        h1 {
                          font-size: 2em;
                          margin: 0.67em 0;
                        }
                        
                        /* Grouping content
                           ========================================================================== */
                        
                        /**
                         * 1. Add the correct box sizing in Firefox.
                         * 2. Show the overflow in Edge and IE.
                         */
                        
                        hr {
                          box-sizing: content-box; /* 1 */
                          height: 0; /* 1 */
                          overflow: visible; /* 2 */
                        }
                        
                        /**
                         * 1. Correct the inheritance and scaling of font size in all browsers.
                         * 2. Correct the odd `em` font sizing in all browsers.
                         */
                        
                        pre {
                          font-family: monospace, monospace; /* 1 */
                          font-size: 1em; /* 2 */
                        }
                        
                        /* Text-level semantics
                           ========================================================================== */
                        
                        /**
                         * Remove the gray background on active links in IE 10.
                         */
                        
                        a {
                          background-color: transparent;
                        }
                        
                        /**
                         * 1. Remove the bottom border in Chrome 57-
                         * 2. Add the correct text decoration in Chrome, Edge, IE, Opera, and Safari.
                         */
                        
                        abbr[title] {
                          border-bottom: none; /* 1 */
                          text-decoration: underline; /* 2 */
                          text-decoration: underline dotted; /* 2 */
                        }
                        
                        /**
                         * Add the correct font weight in Chrome, Edge, and Safari.
                         */
                        
                        b,
                        strong {
                          font-weight: bolder;
                        }
                        
                        /**
                         * 1. Correct the inheritance and scaling of font size in all browsers.
                         * 2. Correct the odd `em` font sizing in all browsers.
                         */
                        
                        code,
                        kbd,
                        samp {
                          font-family: monospace, monospace; /* 1 */
                          font-size: 1em; /* 2 */
                        }
                        
                        /**
                         * Add the correct font size in all browsers.
                         */
                        
                        small {
                          font-size: 80%;
                        }
                        
                        /**
                         * Prevent `sub` and `sup` elements from affecting the line height in
                         * all browsers.
                         */
                        
                        sub,
                        sup {
                          font-size: 75%;
                          line-height: 0;
                          position: relative;
                          vertical-align: baseline;
                        }
                        
                        sub {
                          bottom: -0.25em;
                        }
                        
                        sup {
                          top: -0.5em;
                        }
                        
                        /* Embedded content
                           ========================================================================== */
                        
                        /**
                         * Remove the border on images inside links in IE 10.
                         */
                        
                        img {
                          border-style: none;
                        }
                        
                        /* Forms
                           ========================================================================== */
                        
                        /**
                         * 1. Change the font styles in all browsers.
                         * 2. Remove the margin in Firefox and Safari.
                         */
                        
                        button,
                        input,
                        optgroup,
                        select,
                        textarea {
                          font-family: inherit; /* 1 */
                          font-size: 100%; /* 1 */
                          line-height: 1.15; /* 1 */
                          margin: 0; /* 2 */
                        }
                        
                        /**
                         * Show the overflow in IE.
                         * 1. Show the overflow in Edge.
                         */
                        
                        button,
                        input { /* 1 */
                          overflow: visible;
                        }
                        
                        /**
                         * Remove the inheritance of text transform in Edge, Firefox, and IE.
                         * 1. Remove the inheritance of text transform in Firefox.
                         */
                        
                        button,
                        select { /* 1 */
                          text-transform: none;
                        }
                        
                        /**
                         * Correct the inability to style clickable types in iOS and Safari.
                         */
                        
                        button,
                        [type="button"],
                        [type="reset"],
                        [type="submit"] {
                          -webkit-appearance: button;
                        }
                        
                        /**
                         * Remove the inner border and padding in Firefox.
                         */
                        
                        button::-moz-focus-inner,
                        [type="button"]::-moz-focus-inner,
                        [type="reset"]::-moz-focus-inner,
                        [type="submit"]::-moz-focus-inner {
                          border-style: none;
                          padding: 0;
                        }
                        
                        /**
                         * Restore the focus styles unset by the previous rule.
                         */
                        
                        button:-moz-focusring,
                        [type="button"]:-moz-focusring,
                        [type="reset"]:-moz-focusring,
                        [type="submit"]:-moz-focusring {
                          outline: 1px dotted ButtonText;
                        }
                        
                        /**
                         * Correct the padding in Firefox.
                         */
                        
                        fieldset {
                          padding: 0.35em 0.75em 0.625em;
                        }
                        
                        /**
                         * 1. Correct the text wrapping in Edge and IE.
                         * 2. Correct the color inheritance from `fieldset` elements in IE.
                         * 3. Remove the padding so developers are not caught out when they zero out
                         *    `fieldset` elements in all browsers.
                         */
                        
                        legend {
                          box-sizing: border-box; /* 1 */
                          color: inherit; /* 2 */
                          display: table; /* 1 */
                          max-width: 100%; /* 1 */
                          padding: 0; /* 3 */
                          white-space: normal; /* 1 */
                        }
                        
                        /**
                         * Add the correct vertical alignment in Chrome, Firefox, and Opera.
                         */
                        
                        progress {
                          vertical-align: baseline;
                        }
                        
                        /**
                         * Remove the default vertical scrollbar in IE 10+.
                         */
                        
                        textarea {
                          overflow: auto;
                        }
                        
                        /**
                         * 1. Add the correct box sizing in IE 10.
                         * 2. Remove the padding in IE 10.
                         */
                        
                        [type="checkbox"],
                        [type="radio"] {
                          box-sizing: border-box; /* 1 */
                          padding: 0; /* 2 */
                        }
                        
                        /**
                         * Correct the cursor style of increment and decrement buttons in Chrome.
                         */
                        
                        [type="number"]::-webkit-inner-spin-button,
                        [type="number"]::-webkit-outer-spin-button {
                          height: auto;
                        }
                        
                        /**
                         * 1. Correct the odd appearance in Chrome and Safari.
                         * 2. Correct the outline style in Safari.
                         */
                        
                        [type="search"] {
                          -webkit-appearance: textfield; /* 1 */
                          outline-offset: -2px; /* 2 */
                        }
                        
                        /**
                         * Remove the inner padding in Chrome and Safari on macOS.
                         */
                        
                        [type="search"]::-webkit-search-decoration {
                          -webkit-appearance: none;
                        }
                        
                        /**
                         * 1. Correct the inability to style clickable types in iOS and Safari.
                         * 2. Change font properties to `inherit` in Safari.
                         */
                        
                        ::-webkit-file-upload-button {
                          -webkit-appearance: button; /* 1 */
                          font: inherit; /* 2 */
                        }
                        
                        /* Interactive
                           ========================================================================== */
                        
                        /*
                         * Add the correct display in Edge, IE 10+, and Firefox.
                         */
                        
                        details {
                          display: block;
                        }
                        
                        /*
                         * Add the correct display in all browsers.
                         */
                        
                        summary {
                          display: list-item;
                        }
                        
                        /* Misc
                           ========================================================================== */
                        
                        /**
                         * Add the correct display in IE 10+.
                         */
                        
                        template {
                          display: none;
                        }
                        
                        /**
                         * Add the correct display in IE 10.
                         */
                        
                        [hidden] {
                          display: none;
                        }
                        
                        :root {
                            --blanco: #fff;
                            --negro: #000;
                        }
                        * {
                            box-sizing: border-box;
                            margin: 2px;
                            padding: 2px;
                        }
                         html {
                            font-size: 62.5%
                        }
                        body {
                            font-size: 16px;
                            font-family: \'Protest Strike\', sans-serif;
                            background-color: var(--negro);
                            color: var(--blanco);
                        }
                        h1 , h2 , h3 , h4 , h5 , h6 {
                            font-family: \'Protest Riot\', sans-serif;
                        }
                        h1 {
                            font-size: 3.8rem;
                        }
                        a {
                            text-decoration: none;
                            color: var( --blanco );
                        }
                        a:hover {
                            text-decoration: underline;
                        }
                        p , h1 , h2 , h3 , h4 , h5 , h6 {
                            text-align: center;
                        }
                        ul {
                            list-style: none;
                            display: flex;
                            justify-content: space-evenly;
                            align-items: center;
                            flex-wrap: wrap;
                        }
                        ul li {
                            margin: 0px auto;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                          }
                          th, td {
                            text-align: left;
                            padding: 8px;
                            border: 1px solid #ddd;
                          }
                          th {
                            background-color: #f2f2f2;
                            color: var(--negro);
                          }
                        
                          /* Estilo para dispositivos móviles */
                          @media screen and (max-width: 600px) {
                            table, thead, tbody, th, td, tr {
                              display: block;
                            }
                            thead tr {
                              position: absolute;
                              top: -9999px;
                              left: -9999px;
                            }
                            tr { border: 1px solid #ccc; }
                            td {
                              /* Hacer que los celdas se comporten como "bloques", con un espacio entre ellos */
                              border: none;
                              border-bottom: 1px solid #eee;
                              position: relative;
                              padding-left: 50%;
                            }
                            td:before {
                              /* Contenido del atributo "data-title" */
                              content: attr(data-title);
                              position: absolute;
                              left: 6px;
                              top: 6px;
                              font-weight: bold;
                            }
                          }
                    </style>
                    '.$head.'
                </head>
                <body '.$attributes.'>
                    '.$body.'
                        <p xmlns:cc="http://creativecommons.org/ns#" xmlns:dct="http://purl.org/dc/terms/"> 
                            <a property="dct:title" rel="cc:attributionURL" href="https://adaptowebs.com"> 
                                adaptowebs</a> by <a rel="cc:attributionURL dct:creator" property="cc:attributionName" 
                                href="https://github.com/versoftly"> Ramiro Garcia Gonzalez</a> is licensed under 
                                <a href="http://creativecommons.org/licenses/by-nc-sa/4.0/?ref=chooser-v1" target="_blank" 
                                rel="license noopener noreferrer" style="display:inline-block;"> Attribution-NonCommercial-ShareAlike 4.0 
                                International<img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" 
                                src="https://mirrors.creativecommons.org/presskit/icons/cc.svg?ref=chooser-v1"> 
                                <img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" 
                                src="https://mirrors.creativecommons.org/presskit/icons/by.svg?ref=chooser-v1"> 
                                <img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" 
                                src="https://mirrors.creativecommons.org/presskit/icons/nc.svg?ref=chooser-v1"> 
                                <img style="height:22px!important;margin-left:3px;vertical-align:text-bottom;" 
                                src="https://mirrors.creativecommons.org/presskit/icons/sa.svg?ref=chooser-v1"> 
                            </a> 
                        </p> 
                </body>
            </html>';
        }
        
    }
    
    class Adaptowebs extends Content {
        
        protected $data = [
            "host" => "x_x",
            "db" => "x_x",
            "charset" => "x_x",
            "user" => "x_x",
            "pass" => "x_x"
        ];
        
        public function errors () {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            ini_set('log_errors',1);
        }
        
        public function set_host ($host) {
            $this->data['host'] = $host;
        }
        
        public function set_db ($db) {
            $this->data['db'] = $db;
        }
        
        public function set_charset ($charset) {
            $this->data['charset'] = $charset;
        }
        
        public function set_user ($user) {
            $this->data['user'] = $user;
        }
        
        public function set_pass ($pass) {
            $this->data['pass'] = $pass;
        }
        
        public function get_dbname () {
            return $this->data['db'];
        }
        
         public function pdo ($lab,$mysqli=false) {
            
            $host = $this->data['host'];
            $db = $this->data['db'];
            $charset = $this->data['charset'];
            $user = $this->data['user'];
            $pass = $this->data['pass'];
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset;";
            
            if ($mysqli == false) {
                
                try {
                    $pdo = new PDO($dsn,$user,$pass);
                    $pdo -> setAttribute (PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                    return $pdo;
                } catch (PDOException $e) {
                    die ("error de conexion");
                }
            
            } else {
                
                $mysqli = mysqli_connect ($host,$user,$pass,$db);
                
                if ($mysqli) {
                    return $mysqli;
                } else {
                    die ("error de conexion");
                }
                
            }
            
        }
        
    }
    
    function page ($object,$head,$body,$lang,$attributes) {
    
        $object -> errors ();
        
        $object -> setContent ($head);
        $head_content = $object -> getContent ();
        
        $object -> setContent ($body);
        $body_content = $object -> getContent ();
        
        $object -> build ($head_content,$body_content,$lang,$attributes);
        
        return $object;
        
    }
    
    function peticion () {
        
        $uri = explode ( "/" , $_SERVER['REQUEST_URI'] );
        
        $uri = array_filter ( $uri );
        
        if ( empty ( $uri ) ) {
            return 0;
        } else {
            return $uri;
        }
        
    }
    
    function tipo_peticion ($respuesta) {
        
        if ( count ( $respuesta ) == 1 && isset ( $_SERVER['REQUEST_METHOD'] ) 
                or isset ( $_SERVER['REQUEST_METHOD'] )
            ) {
            
            if ( $_SERVER['REQUEST_METHOD'] == "GET" ) {
                
                http_response_code ( 200 );
                
                return "GET";
                
            }
            
            else if ( $_SERVER['REQUEST_METHOD'] == "POST" ) {
                
                http_response_code ( 200 );
                
                return "POST";
                
            }
            
            else if ( $_SERVER['REQUEST_METHOD'] == "PUT" ) {
                
                http_response_code ( 200 );
                
                return "PUT";
                
            }
            
            else if ( $_SERVER['REQUEST_METHOD'] == "DELETE" ) {
                
                http_response_code ( 200 );
                
                return "DELETE";
                
            }
            
            else {
                
                http_response_code ( 404 );
                
                return "METHOD NOT FOUND.";
                
            }
            
        }
        
    }
    
    function tabla () {
        
        $tabla = $_GET['table'] ?? "users";
        
        return $tabla;
        
    }
    
    function select () {
        
        $select = $_GET['select'] ?? "*";
        
        return $select;
        
    }
    
    function orderby () {
        
        $orderby = $_GET['orderby'] ?? null;
        
        return $orderby;
        
    }
    
    function ordermode () {
        
        $ordermode = $_GET['ordermode'] ?? null;
        
        return $ordermode;
        
    }
    
    function startat () {
        
        $startat = $_GET['startat'] ?? null;
        
        return $startat;
        
    }
    
    function endat () {
        
        $endat = $_GET['endat'] ?? null;
        
        return $endat;
        
    }
    
    function filterto () {
        
        $filter = $_GET['filter'] ?? null;
        
        return $filter;
        
    }
    
    function into () {
        
        $in = $_GET['in'] ?? null;
        
        return $in;
        
    }
    
    function iterar_post ($post) {
        $columns = [];
        foreach ( $post as $key => $value ) {
            array_push($columns,$key);
        }
        return $columns;
    }
    
    function iterar_post_2 ($post) {
        $columns = [];
        foreach ( $post as $key => $value ) {
            array_push($columns,$value);
        }
        return $columns;
    }
    
    function columns_values ($columns , $values) {
        
        if ( count($columns) == count($values) ) {
            return [
                "columns" => $columns ,
                "values" => $values
            ];
        } else {
            echo json_encode(["ERROR"=>"el numero de columnas no coincide con el numero de valores"] , http_response_code (404));
        }
        
    }
    
    function sufijo () {
        
        $sufijo = $_GET['sufijo'] ?? "user";
        
        return $sufijo;
        
    }
    
    function registrar ( $tabla , $data , $conexion , $sufijo , $lab ) {
        
        if ( isset($data['password_'.$sufijo]) && $data['password_'.$sufijo] != null ) {
            
            $crypt = crypt ( $data['password_'.$sufijo] , '$2a$07$kfdhgkjhdfghdkjhg94574hgsd$' );
            
            $data['password_'.$sufijo] = $crypt;
            
            $lab -> postData ( $tabla , columns_values(iterar_post ($data),iterar_post_2 ($data)) , $conexion );
            
        } else {
            
            $result = $lab -> postData ( $tabla , columns_values(iterar_post ($data),iterar_post_2 ($data)) , $conexion );
            
            if ($result) {
                
                $response = $lab -> getDataFilter ( $tabla , "*" , "email_".$sufijo , $data['email_'.$sufijo] , null , null, null , null , $conexion , $lab );
                
                if (!empty($response)) {
                    
                    $token = generarToken ( $response[0]->{"id_".$sufijo} , $response[0]->{"email_".$sufijo} );
                
                    $jwtEncrypt = JWT::encode($token,"kshfkskj563k5h3kjdfhkjds34234",'HS256');
                    
                    $dataToken = [
                        
                        "tabla" => $tabla,
                        "column" => "id_".$sufijo,
                        "token_".$sufijo => $jwtEncrypt,
                        "token_exp_".$sufijo => $token['exp'],
                        "id_".$sufijo => $response[0]->{"id_".$sufijo}
                        
                    ];
                    
                    $lab -> putData ($tabla,$dataToken,$conexion,$lab);
                    
                }
                
            }
            
        }
        
    }
    
    function generarToken ( $userId , $userEmail ) {
        
        $time = time();
        
        $token = [
        
            "iat" => $time, // tiempo de inicio o creacion del token
            "exp" => $time + (60*60*24), // tiempo de vencimiento del token
            "data" => [
                "id" => $userId,
                "email" => $userEmail
            ]
            
        ];
        
        return $token;
        
    }
    
    function iniciarSesion ( $tabla , $data , $sufijo , $conexion , $lab ) {
        
        $response = $lab -> getDataFilter ( $tabla , "*" , "email_".$sufijo , $data['email_'.$sufijo] , null , null, null , null , $conexion , $lab );
                
        if ( empty($response) ) {
                    
            echo json_encode ( 
                [
                    "ERROR" => "El Registro no Existe."
                ] , http_response_code (404)
            );
            die();
            
        } else {
            
            if ( isset($data['password_'.$sufijo]) ) {
            
                $crypt = crypt ( $data['password_'.$sufijo] , '$2a$07$kfdhgkjhdfghdkjhg94574hgsd$' );
                
                if ($response[0]->{"password_".$sufijo} == $crypt) {
                    
                    $token = generarToken ( $response[0]->{"id_".$sufijo} , $response[0]->{"email_".$sufijo} );
                    
                    $jwtEncrypt = JWT::encode($token,"kshfkskj563k5h3kjdfhkjds34234",'HS256');
                    
                    $dataToken = [
                        
                        "tabla" => $tabla,
                        "column" => "id_".$sufijo,
                        "token_".$sufijo => $jwtEncrypt,
                        "token_exp_".$sufijo => $token['exp'],
                        "id_".$sufijo => $response[0]->{"id_".$sufijo}
                        
                    ];
                    
                    $lab -> putData ($tabla,$dataToken,$conexion,$lab);
                    
                } else {
                    
                    echo json_encode ( 
                        [
                            "ERROR" => "El Registro No Existe."
                        ] , http_response_code (404)
                    );
                    die();
                    
                }
                
            } else {
                
                $token = generarToken ( $response[0]->{"id_".$sufijo} , $response[0]->{"email_".$sufijo} );
                    
                $jwtEncrypt = JWT::encode($token,"kshfkskj563k5h3kjdfhkjds34234",'HS256');
                
                $dataToken = [
                    
                    "tabla" => $tabla,
                    "column" => "id_".$sufijo,
                    "token_".$sufijo => $jwtEncrypt,
                    "token_exp_".$sufijo => $token['exp'],
                    "id_".$sufijo => $response[0]->{"id_".$sufijo}
                    
                ];
                
                $lab -> putData ($tabla,$dataToken,$conexion,$lab);
                
            }
            
        }
        
    }
    
    function validarToken ( $token , $conexion , $lab ) {
        
        $user = $lab -> getDataFilter ( tabla() , "token_exp_".sufijo() , "token_".sufijo() , $token , null , null, null , null , $conexion , $lab );
        
        if ( !empty( $user ) ) {
            
            $time = time();
            
            if ( $user[0]->{"token_exp_".sufijo()} > $time ) {
                
                return true;
                
            } else {
                
                return false;
                
            }
            
        } else {
            
            echo json_encode ( [
                "ERROR404" => "User Not Found."    
            ] , http_response_code ( 404 ) );
            
        }
        
    }

?>
