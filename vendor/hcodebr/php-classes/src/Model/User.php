<?php 

namespace Hcode\Model;

Use \Hcode\DB\Sql;
Use \Hcode\Model;

class User extends Model{

	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret";

	public static function getFromSession() {

		if (isset($_SESSION[User::SESSION]) && ((int)$_SESSION[User::SESSION]['iduser'] > 0) {
			
			$user = new user();

			$user->setData($_SESSION[User::SESSION]);

		}

			return $user;

	} 

	public static function checkLogin($inadmin = true) {

		if (
			!isset($_SESSION[User::SESSION]) 
			|| 
			!$_SESSION[User::SESSION] 
			|| 
			!(int)$_SESSION[User::SESSION]["iduser"] > 0 
			) {
			// não está logado
			return false;

		} else {

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true) {
					
					return true;

			} else if($inadmin === false) {

				return true;

			} else {

				return false;

			}

		}

	}

	public static function login($login, $password) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0) {
			throw new \Exception("Usuário inexistente ou senha inválida.");			
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true) {
			
			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}

	}

	public static function verifyLogin($inadmin = true) {

		if (User::checkLogin($inadmin)) {
			
			header("Location: /admin/login");
			exit;
		}

	}

	public static function logout() {

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll() {

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}

	public function save() {

		$sql = new Sql();

		$result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>$this->getdesperson(),
			":despassword"=>$this->getdespassword(),
			":deslogin"=>$this->getdeslogin(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadimin()
		));

		$this->setData($results[0]);

	}

	public function get($iduser) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser
		));

		$this->setData($results[0]);

	}

	public function update() {

				$sql = new Sql();

		$result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":despassword"=>$this->getdespassword(),
			":deslogin"=>$this->getdeslogin(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadimin()
		));

		$this->setData($results[0]);

	}

	public function delete() {

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getuser()
		));

	}

	public static function getForgot($email) {

		$sql = new Sql();


		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email", array(
			":email"=>$email
		));

		if (count($results) === 0) {
			
			throw new \Exception("Não foi possível recuperar a senha.");

		} else {

			$data = $results[0];
			
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:idsuer, :desip)", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results2) === 0) {
				
				throw new Exception("Não foi possível recuperar a senha.");
				
			} else {

				$dataRecovery = $results2[0];

				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

				$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefiner senha da Hcode Store", "forgot", array(
					"name"=>$data["desperson"],
					"link"=>$link
				));

				$mailer->send();

				return $data;

			}

		}

	}

	public static function validForgotDecrypt($code) {

		$idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

		$sql = new Sql();

		$results = $sql->select("
			SELECT * FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b using(iduser)
			INNER JOIN tb_person c using(idperson)
			WHERE a.idrecovery = :idrecovery AND 
			a.idrecovery IS NULL AND 
			DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
			", array(
				":idrecovery"=>$idrecovery
			));

		if (count($results) === 0) {
			
			throw new \Exception("Não foi possível recuperar a senha.");
			
		} else {

			return $results[0];

		}

	}

	public static function setForgotused($idrecovery) {

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NEW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));

	}

	public function setPassword($password) {

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()

		));

	}

}

 ?>