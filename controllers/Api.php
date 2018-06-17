<?php
namespace controllers{

	class Api{
	
		private $PDO;
		private $APP;
		private $return;
 
		function __construct(){
			global $app;
			$this->APP = $app;
			$this->return = (object) ['status' => true, 'data' => null];
			$this->PDO = new \PDO('mysql:host=localhost;dbname=plantapp', 'root', '', array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")); //Conexão
			$this->PDO->setAttribute( \PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION ); //habilitando erros do PDO
			$this->PDO->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_OBJ ); //Retorno em Objeto
			$this->PDO->setAttribute( \PDO::ATTR_ORACLE_NULLS,\PDO::NULL_EMPTY_STRING ); 
		}
		
		public function auth($request){
						
			$acao = $this->PDO->prepare("SELECT * FROM users WHERE username = :username");
			$acao->bindValue(":username", $request->username);			
			$acao->execute();
			$result = $acao->fetch();

			if (!empty($result) && password_verify($request->password, $result->password)) {
				unset($result->password);
				
				$acao = $this->PDO->prepare("SELECT * FROM groups WHERE id = :id");
				$acao->bindValue(":id", $result->group_id);			
				$acao->execute();
				$group = $acao->fetch();

				if (!empty($group)) {
					$group->permissions = json_decode($group->permissions);
					$result->group = $group;
				}


				$this->return->data = $result;
			}else{
				$this->return->status = false;
				$this->return->data = 'Usuario ou senha inválido!';
			}

			return $this->APP->json($this->return); 
		}

		public function getTipos(){
			$acao = $this->PDO->prepare("SELECT * FROM types");
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function getLocais($request){
			$acao = $this->PDO->prepare("SELECT * FROM places WHERE client_id = :client_id and active = 1");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function setLocal($request){

			if (!empty($request->id)) {
				$acao = $this->PDO->prepare("UPDATE places SET name = :name, active = :active WHERE id = :id");
				$acao->bindValue(":id", $request->id);
				$active = $request->active;
			}else{
				$acao = $this->PDO->prepare("INSERT INTO places (client_id, name, active) VALUES (:client_id, :name, :active)");
				$acao->bindValue(":client_id", $request->client_id);
				$active = 1;
			}

			$acao->bindValue(":name", $request->name);
			$acao->bindValue(":active", $active);

			$this->return->data = 'Sucesso!';
			if (!$acao->execute()) {
				$this->return->status = false;
				$this->return->data = 'Erro!';
			}

			return $this->APP->json($this->return); 
		}

		public function getPlantacoes($request){
			$acao = $this->PDO->prepare("SELECT * FROM plantations WHERE place_id = :place_id and active = 1");
			$acao->bindValue(":place_id", $request->place_id);
			$acao->execute();
			$result = $acao->fetchAll();

			foreach ($result as $key => $plant) {
				$acao = $this->PDO->prepare("SELECT types.*, pt.status FROM plants_types pt INNER JOIN types ON (pt.type_id = types.id) WHERE pt.plant_id = :plant_id");
				$acao->bindValue(":plant_id", $plant->id);
				$acao->execute();
				$types = $acao->fetchAll();

				$result[$key]->types = $types;
			}

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function setPlantacao($request){

			if (!empty($request->id)) {
				$acao = $this->PDO->prepare("UPDATE plantations SET micro_id = :micro_id, name = :name, hour_begin = :hour_begin, hour_end = :hour_end, repeated = :repeated, active = :active WHERE id = :id");
				$acao->bindValue(":id", $request->id);
				$active = $request->active;
			}else{
				$acao = $this->PDO->prepare("INSERT INTO plantations (place_id, micro_id, name, hour_begin, hour_end, repeated, active) VALUES (:place_id, :micro_id, :name, :hour_begin, :hour_end, :repeated, :active)");
				$acao->bindValue(":place_id", $request->place_id);
				$active = 1;
			}

			if (empty($request->hour_begin)) {
				$request->hour_begin = null;
			}if (empty($request->hour_begin)) {
				$request->hour_end = null;
			}

			$acao->bindValue(":micro_id", $request->micro_id);
			$acao->bindValue(":name", $request->name);
			$acao->bindValue(":hour_begin", $request->hour_begin);
			$acao->bindValue(":hour_end", $request->hour_end);
			$acao->bindValue(":repeated", (!empty($request->repeat) ? 1 : 0));
			$acao->bindValue(":active", $active);

			$this->return->data = 'Sucesso!';
			if ($acao->execute()) {
				if (!empty($request->id)) {
					$plant_id = $request->id;
				}else{
					$plant_id = $this->PDO->lastInsertId();
				}
			}else{
				$this->return->status = false;
				$this->return->data = 'Erro ao salvar plantação!';
				return $this->APP->json($this->return); 
			}

			$acao = $this->PDO->prepare("DELETE FROM plants_types WHERE plant_id = :plant_id");
			$acao->bindValue(":plant_id", $plant_id);
			$acao->execute();

			foreach ($request->type_id as $type_id) {
				$acao = $this->PDO->prepare("INSERT INTO plants_types (type_id, plant_id) VALUES (:type_id, :plant_id)");
				$acao->bindValue(":type_id", $type_id);
				$acao->bindValue(":plant_id", $plant_id);
				if ($acao->execute()) {
					$this->return->data = 'Sucesso!';
				}else{
					$this->return->status = false;
					$this->return->data = 'Erro ao salvar os tipos!';
					return $this->APP->json($this->return); 
				}
			}

			return $this->APP->json($this->return); 
		}

		public function getGrupos($request){
			$acao = $this->PDO->prepare("SELECT * FROM groups WHERE client_id = :client_id and active = 1");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->execute();
			$result = $acao->fetchAll();

			foreach ($result as $key => $group) {
				$acao = $this->PDO->prepare("SELECT users.* FROM users INNER JOIN groups ON (users.group_id = groups.id) WHERE users.group_id = :group_id and users.active = 1");
				$acao->bindValue(":group_id", $group->id);
				$acao->execute();
				$users = $acao->fetchAll();

				$result[$key]->permissions = json_decode($group->permissions);

				$result[$key]->users = $users;
			}

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function setGrupo($request){
			$acao = $this->PDO->prepare("SELECT * FROM modules");
			$acao->execute();
			$modules = $acao->fetchAll();

			// MONTA OBJETO DE PERMISSOES
			$permissions = (object)[];
			foreach ($modules as $module) {
				$name = strtolower($this->removeAccents($module->name));
				if (!empty($request->$name)) {
					$permissions->$name = $this->buildPermission($request->$name);
				}
			}

			if (!empty($request->id)) {
				$acao = $this->PDO->prepare("UPDATE groups SET name = :name, permissions = :permissions, active = :active WHERE id = :id");
				$acao->bindValue(":id", $request->id);
				$active = $request->active;
			}else{
				$acao = $this->PDO->prepare("INSERT INTO groups (client_id, name, permissions, active) VALUES (:client_id, :name, :permissions, :active)");
				$acao->bindValue(":client_id", $request->client_id);
				$active = 1;
			}

			$acao->bindValue(":name", $request->name);
			$acao->bindValue(":permissions", json_encode($permissions));
			$acao->bindValue(":active", $active);

			$this->return->data = 'Sucesso!';
			if (!$acao->execute()) {
				$this->return->status = false;
				$this->return->data = 'Erro!';
			}

			return $this->APP->json($this->return); 
		}

		public function getUsuarios($request){
			$acao = $this->PDO->prepare("SELECT * FROM users WHERE client_id = :client_id and active = 1");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function setUsuario($request){

			if (!empty($request->changePassword)) {
				$acao = $this->PDO->prepare("UPDATE users SET password = :password WHERE id = :id");
				$acao->bindValue(":id", $request->id);
				$acao->bindValue(":password", password_hash($request->password, PASSWORD_DEFAULT));
			}else{
				if (!empty($request->id)) {
					$acao = $this->PDO->prepare("UPDATE users SET name = :name, group_id = :group_id, phone = :phone, email = :email, username = :username, active = :active WHERE id = :id");
					$acao->bindValue(":id", $request->id);
					$active = $request->active;
				}else{
					$acao = $this->PDO->prepare("INSERT INTO users (client_id, group_id, name, phone, email, username, password, active) VALUES (:client_id, :group_id, :name, :phone, :email, :username, :password, :active)");
					$acao->bindValue(":client_id", $request->client_id);
					$acao->bindValue(":password", password_hash($request->password, PASSWORD_DEFAULT));
					$active = 1;
				}

				$acao->bindValue(":name", $request->name);
				$acao->bindValue(":group_id", $request->group_id);
				$acao->bindValue(":phone", $request->phone);
				$acao->bindValue(":email", $request->email);
				$acao->bindValue(":username", $request->username);
				$acao->bindValue(":active", $active);
			}

			$this->return->data = 'Sucesso!';
			if (!$acao->execute()) {
				$this->return->status = false;
				$this->return->data = 'Erro!';
			}

			return $this->APP->json($this->return); 
		}

		public function uploadImage($request){
			var_dump($request);exit;
			$acao = $this->PDO->prepare("SELECT * FROM users WHERE id = :id");
			$acao->bindValue(":id", $request->id);
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function getCameras($request){
			$acao = $this->PDO->prepare("SELECT * FROM cameras WHERE client_id = :client_id and active = 1");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function setCameras($request){
			if (!empty($request->id)) {
				$acao = $this->PDO->prepare("UPDATE cameras SET name = :name, url = :url, active = :active WHERE id = :id");
				$acao->bindValue(":id", $request->id);
				$active = $request->active;
			}else{
				$acao = $this->PDO->prepare("INSERT INTO cameras (client_id, name, url, active) VALUES (:client_id, :name, :url, :active)");
				$acao->bindValue(":client_id", $request->client_id);
				$active = 1;
			}

			$acao->bindValue(":name", $request->name);
			$acao->bindValue(":url", $request->url);
			$acao->bindValue(":active", $active);

			$this->return->data = 'Sucesso!';
			if (!$acao->execute()) {
				$this->return->status = false;
				$this->return->data = 'Erro!';
			}

			return $this->APP->json($this->return); 
		}

		private function buildPermission($permissions){
			if (is_array($permissions)) {
				foreach ($permissions as $permission) {
					$result[] = $permission;
				}
			}else{
				$result[] = $permissions;
			}

			return $result;
		}

		public function getModules(){
			$acao = $this->PDO->prepare("SELECT * FROM modules");
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}

		public function removeAccents($string){
			$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç", " ", "-" );
			$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C", "_", "_" );
			return str_replace($array1, $array2,$string);
		}

		public function changeStatusPlantation($request){
		    
		    if($this->call_hardware($request)){
				$acao = $this->PDO->prepare("UPDATE plants_types SET status = :status WHERE type_id = :type_id and plant_id = :plant_id");
				$acao->bindValue(":type_id", $request->type_id);
				$acao->bindValue(":plant_id", $request->plant_id);
				$acao->bindValue(":status", $request->status);
				if (!$acao->execute()) {
					$this->return->status = false;
					$this->return->data = 'Erro ao salvar o status da plantação!';
					return $this->APP->json($this->return); 
				} 
		    }else{
		    	$this->return->status = false;
		    	$this->return->data = 'Erro ao mudar o estado do dispositivo!';
		    }

		    return $this->APP->json($this->return);
		}

		public function call_hardware($request){

			$postData = 'plant_id='.$request->plant_id.'&type_id='.$request->type_id.'&status='.$request->status;

			$acao = $this->PDO->prepare("SELECT micro.* FROM plantations plant INNER JOIN microcontrollers micro ON (micro.id = plant.micro_id) WHERE plant.id = :id");
			$acao->bindValue(":id", $request->plant_id);
			$acao->execute();
			$result = $acao->fetch();

			$ch = curl_init();
		    curl_setopt($ch,CURLOPT_URL, 'http://'.$result->external_ip.':'.$result->port);
		    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		    // curl_setopt($ch,CURLOPT_HEADER, false); //if you want headers

		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		    $curl_response = curl_exec($ch);

		    if($curl_response === false)
		    {
		    	$this->return->status = false;
		    	$this->return->data = (object) [
		                "Error Number" => curl_errno($ch),
		                "Error String" => curl_error($ch)
		            ];
		        return false;
		    }
			curl_close($ch);
			return $curl_response;
		}

		public function setMicrocontrollers($request){
			
			$acao = $this->PDO->prepare("SELECT * FROM microcontrollers WHERE external_ip = :external_ip and client_id = :client_id and local_ip = :local_ip");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->bindValue(":external_ip", $request->external_ip);
			$acao->bindValue(":local_ip", $request->local_ip);
			$acao->execute();
			$result = $acao->fetch();

			if (!empty($result)) {
				$acao = $this->PDO->prepare("UPDATE microcontrollers SET external_ip = :external_ip, local_ip = :local_ip, port = :port, active = :active WHERE id = :id");
				$acao->bindValue(":id", $result->id);
				$active = (!empty($request->active) ? $request->active : 1);
			}else{
				$acao = $this->PDO->prepare("INSERT INTO microcontrollers (client_id, external_ip, local_ip, port, active) VALUES (:client_id, :external_ip, :local_ip, :port, :active)");
				$acao->bindValue(":client_id", $request->client_id);
				$active = 1;
			}

			$acao->bindValue(":external_ip", $request->external_ip);
			$acao->bindValue(":local_ip", $request->local_ip);
			$acao->bindValue(":port", $request->port);
			$acao->bindValue(":active", $active);

			$this->return->data = 'Sucesso!';
			if (!$acao->execute()) {
				$this->return->status = false;
				$this->return->data = 'Erro!';
			}

			return $this->APP->json($this->return); 
		}

		public function getMicrocontrollers($request){
			$acao = $this->PDO->prepare("SELECT * FROM microcontrollers WHERE client_id = :client_id");
			$acao->bindValue(":client_id", $request->client_id);
			$acao->execute();
			$result = $acao->fetchAll();

			$this->return->data = $result;

			return $this->APP->json($this->return); 
		}
	}
}