<?php
namespace Amazon\DB;

class Db {

    public function __construct()
    {
        $this->db = new \SQLite3(__DIR__ . "/../amazonRedirect.db");
    }


}