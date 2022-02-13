<?php
namespace webayun\upgradeengine\Core\Sql;

class SqlStatementGenerate
{
	public function generateCreateTableSql($tbname, $columns, $primaryKeys, $uniqueIndexs)
	{
		$sql = "create table `$tbname`";

		$fields = array();
		foreach ($columns as $c) {
			$fields[] = "`" . $c['name'] . "`" .
				$this->generateColumnStatement(
					$c['dataType'],
					$c['type'],
					$c['columnDefault'],
					$c['isNullable'],
					$c['autoIncrement'],
					$c['comment']);
		}

		if (is_array($primaryKeys) && count($primaryKeys) > 0) {
			foreach ($primaryKeys as $kk => $vv) {
				$primaryKeys[$kk] = "`$vv`";
			}
			$fields[] = 'primary key(' . implode(',', $primaryKeys) . ")";
		}

		if (is_array($uniqueIndexs) && count($uniqueIndexs)>0) {
			foreach ($uniqueIndexs as $kk => $vv) {
				$fields[] = "unique index `$vv` (`$vv`)";
			}
		}

		$sql = $sql . "(" . implode(',', $fields) . ")";
		$sql = $sql . " COLLATE='utf8_general_ci' ENGINE=InnoDB";

		return $sql;
	}

	public function generateAlterAddColumnSql($tbname, $columns)
	{
		$fields = array();
		foreach ($columns as $c) {
			$fields[] = $this->addColumnStatement($c['name'], $c['dataType'], $c['type'],
												  $c['columnDefault'], $c['isNullable'],
												  $c['autoIncrement'],
												  $c['comment']);
		}

		$sql = "alter table `$tbname` " . implode(',', $fields);

		return $sql;
	}

	public function generateAlterChangeColumnSql($tbname, $columns)
	{
		$fields = array();
		foreach ($columns as $c) {
			$fields[] = $this->changeColumnStatement($c['name'], $c['dataType'], $c['type'],
													 $c['columnDefault'], $c['isNullable'],
													 $c['autoIncrement'],
													 $c['comment']);
		}

		$sql = "alter table `$tbname` " . implode(',', $fields);

		return $sql;
	}

	public function changeColumnStatement($field, $dataType, $type, $default, $allowNull,$autoIncrement,$comment)
	{
		$statement = $this->generateColumnStatement($dataType, $type, $default, $allowNull, $autoIncrement,$comment);
		$statement = "change column `$field` `$field`" . $statement;

		return $statement;
	}

	public function addColumnStatement($field, $dataType, $columnType, $default, $allowNull,$autoIncrement,$comment)
	{
		$statement = $this->generateColumnStatement($dataType, $columnType, $default, $allowNull,$autoIncrement,$comment);
		$statement = "add column `$field`" . $statement;

		return $statement;
	}

	public function generateColumnStatement($dataType, $columnType, $default, $allowNull, $autoIncrement,$comment)
	{
		if ($allowNull) {
			$null = " null";
		} else {
			$null = " not null";
		}

		if ($autoIncrement) {
			$defaultDefine = " auto_increment";
		} else {

			$defaultDefine = $this->defaultValueStatement($dataType, $default, $allowNull);
		}

		if ($comment) {
			$comments = " COMMENT '{$comment}'";
		} else {
			$comments = " COMMENT ''";
		}

		$statement = " $columnType" . $null . $defaultDefine . $comments;

		return $statement;
	}

	public function defaultValueStatement($dataType, $defaultValue, $allowNull)
	{
		if (false == $allowNull && is_null($defaultValue)) {
			return "";
		}

		$noDefaultType = array('tinyblob', 'mediumblob', 'blob', 'text',
							   'geometry', 'json', 'longblob', 'longtext', 'mediumtext');
		if (in_array(strtolower($dataType), $noDefaultType, true)) {
			return "";
		}

		if (is_null($defaultValue)) {
			$default = " default null";
		} else if ($dataType == 'bit') {
			if ($defaultValue != '') {
				$default = " default b'$defaultValue'";
			} else {
				$default = "";
			}
		} else {
			$default = " default '$defaultValue'";
		}

		return $default;
	}

}
