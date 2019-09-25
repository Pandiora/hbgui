<?php

class DBConnector extends PDO
{

	public function __construct() {

		$base = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
		$pdo = 'sqlite:'.$base.'db/totallynota.db'; 
        parent::__construct($pdo);
    }


    public function run($sql, $bind = NULL)
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($bind);
        return $stmt;
    }

}


?>