<?php
	/* 
		This is an example class script proceeding secured API
		To use this class you should keep same as query string and function name
		Ex: If the query string value rquest=delete_user Access modifiers doesn't matter but function should be
		     function delete_user(){
				 You code goes here
			 }
		Class will execute the function dynamically;
		
		usage :
		
		    $object->response(output_data, status_code);
			$object->_request	- to get santinized input 	
			
			output_data : JSON (I am using)
			status_code : Send status message for headers
			
		Add This extension for localhost checking :
			Chrome Extension : Advanced REST client Application
			URL : https://chrome.google.com/webstore/detail/hgmloofddffdnphfgcellkdfbfbjeloo
		
		I used the below table for demo purpose.
		
		CREATE TABLE IF NOT EXISTS `users` (
		  `user_id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_fullname` varchar(25) NOT NULL,
		  `user_email` varchar(50) NOT NULL,
		  `user_password` varchar(50) NOT NULL,
		  `user_status` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 	*/
	require_once("Rest.inc.php");
    define('AREA', 'D');
    require_once($_SERVER['DOCUMENT_ROOT'].'/init.php');
    use Tygh\Registry;
	class API extends REST {
        const AREA = "D";   
		public $data = "";
		
        const DB_SERVER = "localhost";
        const DB_USER = "root";
        const DB_PASSWORD = "dbpass";
        const DB = "bdname";
		
		private $db = NULL;
        private $auth = NULL;
	
		public function __construct(){
            $this->autharization();
			parent::__construct();				// Init parent contructor
			//$this->dbConnect();					// Initiate Database connection
		}
		
		/*
         *  basic auth 
        */
        private function autharization(){
                $realm = "Restricted area";
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                    header('WWW-Authenticate: Basic realm="My Realm"');
                    header('HTTP/1.0 401 Unauthorized');
                    exit;
                } else {
                    if($this->login()){
                        return true;
                    }else{
                        header('WWW-Authenticate: Basic realm="My Realm"');
                        header('HTTP/1.0 401 Unauthorized');
                        exit;
                    } 
                   
                }
        }

        /*
         *  Database connection 
        */
		private function dbConnect(){
			//$this->db = mysql_connect(self::DB_SERVER,self::DB_USER,self::DB_PASSWORD);
            $this->db = mysqli_connect(Registry::get('config.db_host'),Registry::get('config.db_user'),Registry::get('config.db_password'));
			if($this->db)
				mysql_select_db(Registry::get('config.db_name'),$this->db);
		}
		
		/*
		 * Public method for access api.
		 * This method dynmically call the method based on the query string
		 *
		 */
		public function processApi(){
            //echo "<pre>"; print_r($_REQUEST['rquest']); echo "</pre>";
            $func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response('',404);				// If the method not exist with in this class, response would be "Page not found".
		}
		
		/* 
		 *	Simple login API
		 *  Login must be POST method
		 *  email : <USER EMAIL>
		 *  pwd : <USER PASSWORD>
		 */
		
		private function login(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			/*if($this->get_request_method() != "POST"){
				$this->response('',406);
			}*/
			//$basicauth = explode(" ",$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
			if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])){
				$basicauth = explode(" ",$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
			}elseif (isset($_SERVER['HTTP_AUTHORIZATION'])){ 
				$basicauth = explode(" ",$_SERVER['HTTP_AUTHORIZATION']);
			}	
			/*$email = $this->_request['email'];		
			$password = $this->_request['pwd'];*/
			$file = fopen("auth.csv","r");
			
			
			$login = false;
			while (($line = fgetcsv($file)) !== FALSE) {
				$auth = base64_encode(implode(":",$line));
				if((string)$basicauth[1] === $auth){
					$login = true;
	                break;
	            }
        	}
        	fclose($file);
        	return $login;
		}
		
		private function users(){	
			// Cross validation if the request method is GET else it will return "Not Acceptable" status
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$sql = db_get_row("SELECT user_id, firstname, email FROM ?:users WHERE user_id = 1");
            print_r($sql);
			/*if(mysql_num_rows($sql) > 0){
				$result = array();
				while($rlt = mysql_fetch_array($sql,MYSQL_ASSOC)){
					$result[] = $rlt;
				}
				// If success everythig is good send header as "OK" and return list of users in JSON format
				$this->response($this->json($result), 200);
			}*/
			$this->response('',204);	// If no records "No Content" status
		}


		private function Masking(){
			// Cross validation if the request method is GET else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
				$data = array();
			}else{
				$data = json_decode(file_get_contents('php://input'), true);
					if(!$this->checkparams($data)){
						$response['status']=" Invalid Request";
						$this->response($this->json($response),400);
					}
			}
			$mask_number = $data['mask_number'];
			$response['dial'] = db_get_field("select phone from ?:mask_numbers where mask_number=".$mask_number." AND phone>0");
			$response['dial'] = ($response['dial']!="")?$response['dial']:buyerSupportNumber;
			if(isset($response['dial']) && $response['dial']!=""){
				$response['status'] = 200;
				$this->response($this->json($response),200);
			}else{
				$response['status'] = 400;
				$this->response($this->json($response),400);
			}
				// If no records "No Content" status
		}

		private function Circlewise(){
			// Cross validation if the request method is GET else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
				$data = array();
			}else{
				$data = json_decode(file_get_contents('php://input'), true);
					if(!$this->checkparams($data)){
						$response['status']=" Invalid Request";
						$this->response($this->json($response),400);
					}
			}
			
			$circle_number = $data['circle_number'];
			$pin_number = $data['pin'];
			$response['dial'] = db_get_field("select phone from ?:circle_numbers where circle_number=".$circle_number." AND pin=".$pin_number);
			
			$response['dial'] = ($response['dial']!="")?$response['dial']:buyerSupportNumber;
			if(isset($response['dial']) && $response['dial']!=""){
				$response['status'] = 200;
				$this->response($this->json($response),200);
			}else{
				$response['status'] = 400;
				$this->response($this->json($response),400);
			}
				// If no records "No Content" status
		}
		
		
		private function checkparams($request_params){
			$check =true;
			$request_params = array_keys($request_params);
			$params = array('mask_number','circle_number','pin');
			
			if (array_intersect($request_params, $params) == $request_params) {
				$check =true;
			}else{
				$check =false;
			}
			return $check;
		}
		
		private function PostWelcome(){
			// Cross validation if the request method is GET else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
				$data = array();
			}else{
				$data = json_decode(file_get_contents('php://input'), true);
			}
			
			$sql = db_get_row("SELECT users.user_id,orders.order_id,statuses.description FROM ?:users as users
			INNER JOIN ?:orders as orders ON orders.user_id=users.user_id
			INNER JOIN ?:status_descriptions as statuses ON statuses.status=orders.status AND statuses.type='O'	
			WHERE users.phone ='".$data['mobile']."' order by order_id DESC");
			
			$response = array();
			
			if($sql['order_id']>0){
	           	$response['message_id']=3;
	           	$response['data']=array("order_status"=>$sql['description']);
	           	$this->response($this->json($response),200);
           	}else{
           		$response['message_id']=4;
           		$this->response($this->json($response),204);
           	}
				// If no records "No Content" status
		}

		
		private function deleteUser(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			if($id > 0){				
				mysql_query("DELETE FROM users WHERE user_id = $id");
				$success = array('status' => "Success", "msg" => "Successfully one record deleted.");
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}
		
		/*
		 *	Encode array into JSON
		*/
		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>
