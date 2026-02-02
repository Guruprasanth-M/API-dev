<?php
error_reporting(E_ALL ^ E_DEPRECATED);
include 'libs/load.php';

class API extends REST
{
    public $data = "";

    private $db = NULL;

    public function __construct(){
        parent::__construct();
        $this->dbConnect();
    }

    private function dbConnect(){
        if ($this->db != NULL) {
            return $this->db;
        } else {
            $this->db = Database::getConnection();
            if (!$this->db) {
                die("Connection failed");
            } else {
                return $this->db;
            }
        }
    }

    public function processApi(){
        $func = strtolower(trim(str_replace("/","",$_REQUEST['request'] ?? $_REQUEST['rquest'] ?? '')));
        if((int)method_exists($this,$func) > 0)
            $this->$func();
        else
            $this->response('',400);
    }


    private function about(){
        if($this->get_request_method() != "POST"){
            $error = array('status' => 'WRONG_CALL', "msg" => "The type of call cannot be accepted by our servers.");
            $error = $this->json($error);
            $this->response($error,406);
        }
        $data = array('version' => '0.1', 'desc' => 'This API is created by GURUPRASANTH. For learning purpose.');
        $data = $this->json($data);
        $this->response($data,200);
    }


    private function json($data){
        if(is_array($data)){
            return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
}

$api = new API;
$api->processApi();
?>