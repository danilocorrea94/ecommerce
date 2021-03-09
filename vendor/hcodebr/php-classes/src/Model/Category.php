<?php 

namespace Hcode\Model;

Use \Hcode\DB\Sql;
Use \Hcode\Model;

class Category extends Model{

	public static function listAll() {

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");

	}

	public function save() {

		$sql = new Sql();

		$result = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory()
		));

		$this->setData($result[0]);

	}

	public function get($idcategory) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$idcategory
		]);

		$this->setData($results);

	}

	public function delete() {

		$sql = new Sql();

		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$this->getcategory()
		]);

	}

}

 ?>