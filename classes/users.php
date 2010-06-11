<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Users Class
 *
 */
class Users extends ArrayObject
{
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * Returns a user or users based on supplied parameters.
	 * @todo This class should cache query results!
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return array An array of User objects, or a single User object, depending on request
	 **/
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';
		// what to select -- by default, everything
		foreach ( User::default_fields() as $field => $value ) {
			$select .= ( '' == $select )
				? "{users}.$field"
				: ", {users}.$field";
		}
		// defaults
		$orderby = 'id ASC';
		$nolimit = TRUE;

		// Put incoming parameters into the local scope
		$paramarray = Utils::get_params( $paramarray );

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		$wheres = array();
		$join = '';
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[] = $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// safety mechanism to prevent empty queries
				$where = array();
				$paramset = array_merge((array) $paramarray, (array) $paramset);

				$default_fields = User::default_fields();
				foreach ( User::default_fields() as $field => $scrap ) {
					if ( !isset( $paramset[$field] ) ) {
						continue;
					}
					switch ( $field ) {
						case 'id':
							if ( !is_numeric( $paramset[$field] ) ) {
								continue;
							}
						default:
							$where[] = "{$field} = ?";
							$params[] = $paramset[$field];
					}
				}

				if ( isset( $paramset['info'] ) && is_array( $paramset['info'] ) ) {
					$join .= 'INNER JOIN {userinfo} ON {users}.id = {userinfo}.user_id';
					foreach ( $paramset['info'] as $info_name => $info_value ) {
						$where[] = '{userinfo}.name = ? AND {userinfo}.value = ?';
						$params[] = $info_name;
						$params[] = $info_value;
					}
				}

				if ( count($where) > 0 ) {
					$wheres[] = ' (' . implode( ' AND ', $where ) . ') ';
				}
			}
		}

		// Get any full-query parameters
		$possible = array( 'fetch_fn', 'count', 'nolimit', 'limit', 'offset' );
		foreach ( $possible as $varname ) {
			if ( isset( $paramarray[$varname] ) ) {
				$$varname = $paramarray[$varname];
			}
		}

		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn = $fns[0];
			}
		}
		else {
			$fetch_fn = $fns[0];
		}

		// is a count being request?
		if ( isset( $count ) ) {
			$select = "COUNT($count)";
			$fetch_fn = 'get_value';
			$orderby = '';
		}
		if ( isset( $limit ) ) {
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) ) {
			$limit = '';
		}

		$query = '
			SELECT ' . $select
			. ' FROM {users} '
			. $join;

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query .= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;
		//Utils::debug($paramarray, $fetch_fn, $query, $params);

		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('User');
		$results = DB::$fetch_fn( $query, $params, 'User' );

		if ( 'get_results' != $fetch_fn ) {
			// return the results
			return $results;
		}
		elseif ( is_array( $results ) ) {
			$c = __CLASS__;
			$return_value = new $c( $results );
			$return_value->get_param_cache = $paramarray;
			return $return_value;
		}
	}

	/**
	 * Select a user from the database by userinfo
	 *
	 * @param string|array $key
	 * @param string|null $value
	 * @return Users|bool
	 */
	public static function get_by_info( $key, $value = NULL )
	{
		// If no value was specified, check if several info were passed
		if ( NULL === $value ) {
			if ( is_array( $key ) ) {
				$params['info'] = $key;
			}
			else {
				// We need a value to compare to
				return false;
			}
		}
		else {
			$params['info'] = array( $key => $value );
		}

		return self::get( $params );
	}

	/**
	 * Select all users from the database
	 *
	 * @return Users
	 */
	public static function get_all()
	{
		$params = array(
			'orderby' => 'username ASC'
		);

		return self::get( $params );
	}

}
?>
