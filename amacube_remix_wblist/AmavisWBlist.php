<?php
/**
* This file is part of the Amacube-Remix_WBList Roundcube plugin
* Copyright (C) 2015, Tony VanGerpen <Tony.VanGerpen@hotmail.com>
*
* A Roundcube plugin to let users manage whitelist/blacklist (which must be stored in a database)
* Based heavily on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
*
* Licensed under the GNU General Public License version 3.
* See the COPYING file in parent directory for a full license statement. */

class AmavisWBlist {
	# Database Settings
	private $db_config;
	protected $db_conn;

	# User Settings
	private $user_id = '';
	protected $user_email = '';

	function __construct( $db_config ) {
		$this->db_config = $db_config;

		# Fetch Username
		$rcmail = rcmail::get_instance();
		$this->user_email = $rcmail->user->data['username'];
	}

	function init_db() {
		# Initialize Database Factory
		if ( ! $this->db_conn ) {
			if ( ! class_exists( 'rcube_db' ) ) // pre 0.9
				$this->db_conn = new rcube_mdb2( $this->db_config, '', TRUE );
			else // ver 0.9+
				$this->db_conn = rcube_db::factory( $this->db_config, '', TRUE );
		}

		# Connect to the Database
		$this->db_conn->db_connect( 'w' );

		# Check DB connections and exit on failure
		if ( $err_str = $this->db_conn->is_error() ) {
			raise_error( array( 'code' => 603, 'type' => 'db', 'message' => $err_str ), true, true );
		}
	}

	function db_error() {
		# Return the last database error message
		if( $this->db_conn && $this->db_conn->is_error() )
			return $this->db_conn->is_error();

		return false;
	}

	function fetch_user_id() {
		$query = 'SELECT `id` FROM `users` WHERE `email` = ?';
		$records = $this->db_conn->query( $query, $this->user_email );
		
		if( ! empty( $this->db_error ) ) {
			error_log( 'AMACUBE: ' . $this->db_error() );
				return false;
		}

		if( $records->rowCount() == 1 ) {
			$record = $this->db_conn->fetch_assoc( $records );

			$this->user_id = $record['id'];
		}

		return true;
	}

	function fetch_sender_id( $sender ) {
		$query = "SELECT `id` FROM `mailaddr` WHERE `email` = ?";
		$records = $this->db_conn->query( $query, $sender );

		if( ! empty( $this->db_error ) ) {
			error_log( 'AMACUBE: ' . $this->db_error() );
			return false;
		}

		if( count( $errors ) == 0 && $records->rowCount() == 1 ) {
			$record = $this->db_conn->fetch_assoc( $records );

			return $record['id'];
		}

		return 0;
	}

	function fetch_list( $criteria ) {
		$errors = array();
		$items = array();

		# Validate Search Criteria
		if( !is_array( $criteria ) )
			$errors[] = "Invalid Search Criteria. Array expected, " . gettype( $criteria ) . "received.\n";

		if( !is_resource( $this->db_conn ) )
			$this->init_db();

		if( empty( $this->user_id ) )
			if( ! $this->fetch_user_id() )
				$errors[] = "Unable to determine User Id.\n";

		if( count( $errors ) == 0 ) {
			$query = "SELECT `sid`, `email`, `wb` FROM `mailaddr`, `wblist` WHERE `mailaddr`.`id` = `wblist`.`sid` AND `wblist`.`rid` = ?";

			// Order By Hook Also maps table col names to db field names
			switch( $criteria['sort_by'] ) {
				case 'policy':
					$query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY wb " . $criteria['sort_order'] : " ORDER BY subject DESC";
					break;
				case 'sender':
					$query .= ( isset( $criteria['sort_order'] ) && (
					$criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY email " . $criteria['sort_order'] : " ORDER BY sender DESC";
					break;
				default:
					$query .= " ORDER BY `sender` ASC"; break;
			}

			$results = $this->db_conn->query( $query, $this->user_id );

			if( !empty( $this->db_error ) ) {
				$errors[] = $this->db_error; return "Error in selecting wblist: " . $this->db_error();
			}

			$result = array();

			while( $results && ( $result = $this->db_conn->fetch_assoc( $results ) ) )
				$items[] = $result;
		}

		# Return Errors and Items array
		$output = array( 'errors' => $errors, 'items' => $items );

		return $output;
	}

	function add( $sender_address, $policy ) {
		$errors = array(); $sender_id = 0;

		if( !is_string( $sender_address ) && !is_valid_sender_address( $sender_address ) )
			$errors[] = "Invalid Sender Address: {$sender_address}\n";

		# Validate Policy
		switch( $policy ) {
			case 'B':
			case 'W':
				// Do Nothing, valid policy
				break;
			default:
				$errors[] = "Invalid Policy: {$policy}\n";
				break;
		}

		if( !is_resource( $this->db_conn ) )
			$this->init_db();

		if( empty( $this->user_id ) )
			if( ! $this->fetch_user_id() )
				$errors[] = "Unable to determine User Id.\n";

		if( count( $errors ) == 0 ) {
			# Check for entry existence in wblist
			$query = 'SELECT `sid` FROM `mailaddr`, `wblist` WHERE `mailaddr`.`id` = `wblist`.`sid` AND `wblist`.`rid` = ? AND `mailaddr`.`email` = ?';

			$results = $this->db_conn->query( $query, $this->user_id, $sender_address );

			if( !empty( $this->db_error ) )
				$errors[] = "Query Error ({$query}): " . $this->db_error() . "\n";
			else if( $results->rowCount() > 0 )
				$errors[] = "Entry for '{$sender_address}' already exists in your wblist.\n";

			if( count( $errors ) == 0 ) {
				# Does Sender Address Exist in Mailaddr Table?
				$sender_id = $this->fetch_sender_id( $sender_address );

				if( $sender_id == "0" ) {
					# Insert New mailaddr record
					$query = "INSERT INTO `mailaddr` ( `email` ) VALUES ( ? )";
					$results = $this->db_conn->query( $query, $sender_address );

					if( !empty( $this->db_error ) )
						$errors[] = "Query Error ({$query})" . $this->db_error() . "\n";
					else
						$sender_id = $this->db_conn->insert_id();
				}

				if( !empty( $sender_id ) && $sender_id > 0 ) {
					# Insert New wblist record
					$query = "INSERT INTO `wblist` ( `rid`, `sid`, `wb` ) VALUES ( ?, ?, ? )";

					$this->db_conn->query( $query, $this->user_id, $sender_id, $policy );

					if( !empty( $this->db_error ) )
						$errors[] = "Query Error ({$query})" . $this->db_error() . "\n";
				}
			}
		}

		if( count( $errors ) > 0 ) {
			foreach( $errors as $error )
				error_log( 'AMACUBE: add wblist error: ' . $error );

			return $errors;
		}

		return false;
	}

	function delete( $sender_id ) {
		$errors = array();

		if( !is_string( $sender_id ) || !is_numeric( $sender_id ) )
			$errors[] = "Invalid Sender_Id: {$sender_id}\n";

		if( !is_resource( $this->db_conn ) )
			$this->init_db();

		if( empty( $this->user_id ) )
			if( !$this->fetch_user_id() )
				$errors[] = "Unable to determine User Id.\n";

		if( count( $errors ) == 0 ) {
			# Check for wblist entry existence
			$query = 'SELECT `sid` FROM `wblist` WHERE `rid` = ? AND `sid` = ?';

			$results = $this->db_conn->query( $query, $this->user_id, $sender_id );

			if( !empty( $this->db_error ) )
				$errors[] = "Query Error ({$query}): " . $this->db_error() . "\n";
			elseif( $results->rowCount() < 1 )
				$errors[] = "No Entry with Sender Id '{$sender_id}' exists in your wblist.\n";

			if( count( $errors ) == 0 ) {
				# Delete User's WBList Entry
				$query = 'DELETE FROM `wblist` WHERE `rid` = ? AND `sid` = ?';
				$this->db_conn->query( $query, $this->user_id, $sender_id );

				# Delete Sender from mailaddr table if no wblist entries exist for any user.
				$query = 'SELECT `sid` FROM `wblist` WHERE `sid` = ?';
				$results = $this->db_conn->query( $query, $sender_id );

				if( !empty( $this->db_error ) )
					$errors[] = "Query Error ({$query}): " . $this->db_error() . "\n";
				elseif( $results->rowCount() == '0' ) {
					$query = 'DELETE FROM `mailaddr` WHERE `id` = ?';
					$this->db_conn->query( $query, $sender_id );
				}
			}
		}

		if( count( $errors ) > 0 ) {
			foreach( $errors as $error )
				error_log( 'AMACUBE: delete wblist error: ' . $error );

			return $errors;
		}

		return false;
	}

	function is_valid_sender_address( $sender_address ) {
		if( empty( $sender_address ) )
			return false;

		$address = explode( '@', $sender_address );

		if( count( $address ) < 2 ) {
			// No RHS present. Create policy based on LHS of address
			if( filter_var( $sender_address . '@example.org', FILTER_VALIDATE_EMAIL ) )
				return true;
		} else if( count( $address ) < 3 ) {
			if( empty( $address[0] ) ) {
				// No LHS present. Create polocy based on RHS of address
				if( filter_var( 'example@' . $sender_address, FILTER_VALIDATE_EMAIL ) )
					return true;
			} else {
				// LHS and RHS present. Create policy for specific address
				if( filter_var( $sender_address, FILTER_VALIDATE_EMAIL ) )
					return true;
			}
		}

		return false;
	}
}
?>