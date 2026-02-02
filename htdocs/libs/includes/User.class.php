<?php

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function verify($username, $password)
    {
        if($username == "admin" && $password == "admin123"){
            return array('status' => 'SUCCESS', 'msg' => 'User verified successfully.');
        } else {
            return array('status' => 'FAILED', 'msg' => 'Invalid credentials.');
        }
    }

}
