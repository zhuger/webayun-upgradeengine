<?php
namespace webayun\upgradeengine\Core\Sql;

class SchemaDiff
{
	public function findCreateTables($master, $slave)
	{

		$diff_tables = array_diff_key($master, $slave);

		return $diff_tables;
	}

	public function findExistsTables($master, $slave)
	{

		$intersect_tables = array_intersect_key($master, $slave);

		return $intersect_tables;
	}

	public function findExistsColumns($master, $slave)
	{
		$intersect = array_intersect_key($master, $slave);

		return $intersect;
	}

	public function findNotExistsColumns($master, $slave)
	{
		$columns = array_diff_key($master, $slave);

		return $columns;
	}

	public function findChangeColumnTablesDiff($master, $slave)
	{
		$tables = $this->findExistsTables($master, $slave);

		$ret = array();

		// find intersect column's table and return that
		foreach ($tables as $k => $v) {
			$columns = $this->findExistsColumns($v['columns'], $slave[$k]['columns']);

			// find differenece columns, if exists return, else skip this table
			$differences = array();
			foreach ($columns as $ck => $cv) {
				$slave_column = $slave[$k]['columns'][$ck];
				if (!$this->columnEquals($cv, $slave_column)) {
					// make intersection when type is enum
					if ($cv['dataType'] == 'enum') {
						if ($slave_column['dataType'] == 'enum') {
							$slave_column_type  = $this->getParenthesesLimitValue($slave_column['type']);
							$master_column_type = $this->getParenthesesLimitValue($cv['type']);

							$a = explode(',', $master_column_type);
							$b = explode(',', $slave_column_type);

							$c = array_merge($a, $b);
							$c = implode(',', array_unique($c));

							$cv['type'] = "enum($c)";
						}
					}

					$differences[$ck] = $cv;
				}
			}
			if (count($differences) > 0) {
				$ret[$k]            = $v;
				$ret[$k]['columns'] = $differences;
			}
		}

		return $ret;
	}

	public function columnEquals($master, $slave)
	{
		if (is_array($master) && is_array($slave)) {

			$dataTypeEq    = $master['dataType'] == $slave['dataType'];
			$columnDefault = $master['columnDefault'] == $slave['columnDefault'];
			$nullable      = $master['isNullable'] == $slave['isNullable'];
			$comment       = $master['comment'] == $slave['comment'];
			if ($master['dataType'] == 'enum') {
				$typeEq = $master['type'] == $slave['type'];
			} else if ($master['dataType'] == 'decimal') {
				if ($master['type'] == $slave['type']) {
					$typeEq = true;
				} else {
					$pm = explode(',', $this->getParenthesesLimitValue($master['type']));
					$ps = explode(',', $this->getParenthesesLimitValue($slave['type']));
					if ($pm[0] - $pm[1] >= $ps[0] - $ps[1]) {
						$typeEq = false;
					} else {
						$typeEq = true;
					}
				}
			} else {
				$pm = (int)$this->getParenthesesLimitValue($master['type']);
				$ps = (int)$this->getParenthesesLimitValue($slave['type']);

				$typeEq = $pm <= $ps;
			}


			return $typeEq && $dataTypeEq && $columnDefault && $nullable && $comment;
		} else {
			return false;
		}
	}

	public function getParenthesesLimitValue($type)
	{
		$start = strpos($type, '(');
		$end   = strpos($type, ')');

		if ($start !== false && $end !== false) {
			$ret = substr($type, $start + 1, $end - $start - 1);

			return $ret;
		} else {
			return "";
		}
	}



}
