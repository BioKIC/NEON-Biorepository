<?php

class SSP {
	/**
	 * Create the data output array for the DataTables rows
	 *
	 *  @param  array $columns Column information array
	 *  @param  array $data    Data from the SQL get
	 *  @return array          Formatted data in a row based format
	 */
	static function data_output ( $columns, $data )
	{
		$out = array();

		for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
			$row = array();

			for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
				$column = $columns[$j];

				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
                    if(empty($column['db'])){
                        $row[ $column['dt'] ] = $column['formatter']( $data[$i] );
                    }
                    else{
                        $row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
                    }
				}
				else {
                    if(!empty($column['db'])){
                        $row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
                    }
                    else{
                        $row[ $column['dt'] ] = "";
                    }
				}
			}

			$out[] = $row;
		}

		return $out;
	}


	/**
	 * Database connection
	 *
	 * Obtain an PHP PDO connection from a connection details array
	 *
	 *  @param  mixed $conn SQL connection details. The array should have
	 *    the following properties
	 *     * host - host name
	 *     * db   - database name
	 *     * user - user name
	 *     * pass - user password
	 *     * Optional: `'charset' => 'utf8'` - you might need this depending on your PHP / MySQL config
	 *  @return PDO PDO connection
	 */
	static function db ()
	{
		include_once('../../config/dbconnection.php');
		$conn = MySQLiConnectionFactory::getCon("readonly");
		return $conn;
	}


	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL limit clause
	 */
	static function limit ( $request, $columns )
	{
		$limit = '';

		if ( isset($request['start']) && $request['length'] != -1 ) {
			$limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
		}

		return $limit;
	}


	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL order by clause
	 */
	static function order ( $request, $columns )
	{
		$order = '';

		if ( isset($request['order']) && count($request['order']) ) {
			$orderBy = array();
			$dtColumns = self::pluck( $columns, 'dt' );

			for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
				$columnIdx = $request['order'][$i]['column'];
				$requestColumn = $request['columns'][$columnIdx];
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $request['order'][$i]['dir'] === 'asc' ?
						'ASC' :
						'DESC';

					$orderBy[] = '`'.$column['db'].'` '.$dir;
				}
			}

			if ( count( $orderBy ) ) {
				$order = 'ORDER BY '.implode(', ', $orderBy);
			}
		}

		return $order;
	}


	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *  @return string SQL where clause
	 */
	static function filter ( $request, $columns, &$bindings )
	{
		$globalSearch = array();
		$columnSearch = array();
		$dtColumns = self::pluck( $columns, 'dt' );

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {
					if(!empty($column['db'])){
						$binding = self::bind( $bindings, '%'.$str.'%', 's' );
						$globalSearch[] = "`".$column['db']."` LIKE ".$binding;
					}
				}
			}
		}

		// Individual column filtering
		if ( isset( $request['columns'] ) ) {
			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				$str = $requestColumn['search']['value'];

				if ( $requestColumn['searchable'] == 'true' &&
				 $str != '' ) {
					if(!empty($column['db'])){
						$binding = self::bind( $bindings, '%'.$str.'%', 's' );
						$columnSearch[] = "`".$column['db']."` LIKE ".$binding;
					}
				}
			}
		}

		// Combine the filters into a single string
		$where = '';

		if ( count( $globalSearch ) ) {
			$where = '('.implode(' OR ', $globalSearch).')';
		}

		if ( count( $columnSearch ) ) {
			$where = $where === '' ?
				implode(' AND ', $columnSearch) :
				$where .' AND '. implode(' AND ', $columnSearch);
		}

		if ( $where !== '' ) {
			$where = 'WHERE '.$where;
		}

		return $where;
	}


	/**
	 * Perform the SQL queries needed for an server-side processing requested,
	 * utilising the helper functions of this class, limit(), order() and
	 * filter() among others. The returned array is ready to be encoded as JSON
	 * in response to an SSP request, or can be modified if needed before
	 * sending back to the client.
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array|PDO $conn PDO connection resource or connection parameters array
	 *  @param  string $table SQL table to query
	 *  @param  string $primaryKey Primary key of the table
	 *  @param  array $columns Column information array
	 *  @return array          Server-side processing response array
	 */
	static function simple ( $request, $table, $primaryKey, $columns )
	{
		$bindings = array();
		$db = self::db();

		// Allow for a JSON string to be passed in
		if (isset($request['json'])) {
			$request = json_decode($request['json'], true);
		}

		// Build the SQL query string from the request
		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$where = self::filter( $request, $columns, $bindings );

		// Main query to actually get the data
		$data = self::sql_exec( $db, $bindings,
			"SELECT `".implode("`, `", self::pluck($columns, 'db'))."`
			 FROM `$table`
			 $where
			 $order
			 $limit"
		);

		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db, $bindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db,
			[],
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`"
		);
		$recordsTotal = $resTotalLength[0][0];

		/*
		 * Output
		 */
		return array(
			"draw"            => isset ( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			"recordsTotal"    => intval( $recordsTotal ),
			"recordsFiltered" => intval( $recordsFiltered ),
			"data"            => self::data_output( $columns, $data )
		);
	}


	/**
	 * The difference between this method and the `simple` one, is that you can
	 * apply additional `where` conditions to the SQL queries. These can be in
	 * one of two forms:
	 *
	 * * 'Result condition' - This is applied to the result set, but not the
	 *   overall paging information query - i.e. it will not effect the number
	 *   of records that a user sees they can have access to. This should be
	 *   used when you want apply a filtering condition that the user has sent.
	 * * 'All condition' - This is applied to all queries that are made and
	 *   reduces the number of records that the user can access. This should be
	 *   used in conditions where you don't want the user to ever have access to
	 *   particular records (for example, restricting by a login id).
	 *
	 * In both cases the extra condition can be added as a simple string, or if
	 * you are using external values, as an assoc. array with `condition` and
	 * `bindings` parameters. The `condition` is a string with the SQL WHERE
	 * condition and `bindings` is an assoc. array of the binding names and
	 * values.
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array|PDO $conn PDO connection resource or connection parameters array
	 *  @param  string $table SQL table to query
	 *  @param  string $primaryKey Primary key of the table
	 *  @param  array $columns Column information array
	 *  @param  string|array $whereResult WHERE condition to apply to the result set
	 *  @param  string|array $whereAll WHERE condition to apply to all queries
	 *  @return array          Server-side processing response array
	 */
	static function complex (
		$request,
		$table,
		$primaryKey,
		$columns,
		$whereResult=null,
		$whereAll=null
	) {
		$bindings = array();
		$whereAllBindings = array();
		$db = self::db();
		$whereAllSql = '';

		// Build the SQL query string from the request
		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$where = self::filter( $request, $columns, $bindings );

		// whereResult can be a simple string, or an assoc. array with a
		// condition and bindings
		if ( $whereResult ) {
			$str = $whereResult;

			if ( is_array($whereResult) ) {
				$str = $whereResult['condition'];

				if ( isset($whereResult['bindings']) ) {
					self::add_bindings($bindings, $whereResult['bindings']);
				}
			}

			$where = $where ?
				$where .' AND '.$str :
				'WHERE '.$str;
		}

		// Likewise for whereAll
		if ( $whereAll ) {
			$str = $whereAll;

			if ( is_array($whereAll) ) {
				$str = $whereAll['condition'];

				if ( isset($whereAll['bindings']) ) {
					self::add_bindings($whereAllBindings, $whereAll['bindings']);
					$bindings = array_merge($bindings, $whereAllBindings);
				}
			}

			$where = $where ?
				$where .' AND '.$str :
				'WHERE '.$str;

			$whereAllSql = 'WHERE '.$str;
		}

		// Main query to actually get the data
		$data = self::sql_exec( $db, $bindings,
			"SELECT `".implode("`, `", self::pluck($columns, 'db'))."`
			 FROM `$table`
			 $where
			 $order
			 $limit"
		);

		// Data set length after filtering
		$resFilterLength = self::sql_exec( $db, $bindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table`
			 $where"
		);
		$recordsFiltered = $resFilterLength[0][0];

		// Total data set length
		$resTotalLength = self::sql_exec( $db, $whereAllBindings,
			"SELECT COUNT(`{$primaryKey}`)
			 FROM   `$table` ".
			$whereAllSql
		);
		$recordsTotal = $resTotalLength[0][0];

		/*
		 * Output
		 */
		return array(
			"draw"            => isset ( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			"recordsTotal"    => intval( $recordsTotal ),
			"recordsFiltered" => intval( $recordsFiltered ),
			"data"            => self::data_output( $columns, $data )
		);
	}

	/**
	 * Execute an SQL query on the database
	 *
	 * @param  PDO $db  Database handler
	 * @param  array    $bindings Array of PDO binding values from bind() to be
	 *   used for safely escaping strings. Note that this can be given as the
	 *   SQL query string if no bindings are required.
	 * @param  string   $sql SQL query to execute.
	 * @return array         Result from the query (all rows)
	 */
	static function sql_exec($db, $bindings, $sql = null)
	{
		// Argument shifting
		if ($sql === null) {
			$sql = $bindings;
			$bindings = [];
		}
	
		$stmt = $db->prepare($sql);
	
		if (!$stmt) {
			self::fatal("Prepare failed: " . $db->error);
		}
	
		// Bind parameters
		if (!empty($bindings)) {
			$types = '';
			$values = [];
	
			foreach ($bindings as $binding) {
				$types .= $binding['type'];  // 's' for string, 'i' for integer, etc.
				$values[] = $binding['val'];
			}
	
			// bind_param requires references
			$refs = [];
			foreach ($values as $key => $value) {
				$refs[$key] = &$values[$key];
			}
	
			array_unshift($refs, $types);
			call_user_func_array([$stmt, 'bind_param'], $refs);
		}

		// DEBUG: Reconstruct SQL for logging
		$debugSql = $sql;
		$debugValues = [];
		
		foreach ($bindings as $binding) {
			$val = $binding['val'];
			if (is_string($val)) {
				$val = "'" . $db->real_escape_string($val) . "'";
			} elseif (is_null($val)) {
				$val = 'NULL';
			}
			$debugValues[] = $val;
		}
		
		$parts = explode('?', $debugSql);
		$finalSql = '';
		foreach ($parts as $i => $part) {
			$finalSql .= $part;
			if (isset($debugValues[$i])) {
				$finalSql .= $debugValues[$i];
			}
		}
	
		// Execute
		if (!$stmt->execute()) {
			self::fatal("Execute failed: " . $stmt->error);
		}
	
		// Fetch all results
		$result = $stmt->get_result();
		$data = [];
	
		if ($result) {
			while ($row = $result->fetch_array(MYSQLI_BOTH)) {
				$data[] = $row;
			}
			$result->free();
		}
	
		$stmt->close();
	
		return $data;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */

	/**
	 * Throw a fatal error.
	 *
	 * This writes out an error message in a JSON string which DataTables will
	 * see and show to the user in the browser.
	 *
	 * @param  string $msg Message to send to the client
	 */
	static function fatal ( $msg )
	{
		echo json_encode( array( 
			"error" => $msg
		) );

		exit(0);
	}

	/**
	 * Create a PDO binding key which can be used for escaping variables safely
	 * when executing a query with sql_exec()
	 *
	 * @param  array &$a    Array of bindings
	 * @param  mixed  $val  Value to bind
	 * @param  int    $type PDO field type
	 * @return string       Bound key to be used in the SQL where this parameter
	 *   would be used.
	 */
	static function bind ( &$a, $val, $type ) {
		$a[] = array(
			'val' => $val,
			'type' => $type
		);
	
		return '?';
	}


	static function add_bindings(&$a, $bindings) {
		foreach ($bindings as $binding) {
			$a[] = array(
				'val' => $binding['val'],
				'type' => $binding['type']
			);
		}
	}



	/**
	 * Pull a particular property from each assoc. array in a numeric array, 
	 * returning and array of the property values from each item.
	 *
	 *  @param  array  $a    Array to get data from
	 *  @param  string $prop Property to read
	 *  @return array        Array of property values
	 */
	static function pluck ( $a, $prop )
	{
		$out = array();

		for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
 			if ( empty($a[$i][$prop]) && $a[$i][$prop] !== 0 ) {
				continue;
			}

			//removing the $out array index confuses the filter method in doing proper binding,
			//adding it ensures that the array data are mapped correctly
			$out[$i] = $a[$i][$prop];
		}

		return $out;
	}


	/**
	 * Return a string from an array or a string
	 *
	 * @param  array|string $a Array to join
	 * @param  string $join Glue for the concatenation
	 * @return string Joined string
	 */
	static function _flatten ( $a, $join = ' AND ' )
	{
		if ( ! $a ) {
			return '';
		}
		else if ( is_array($a) ) {
			return implode( $join, $a );
		}
		return $a;
	}
}

