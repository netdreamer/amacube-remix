 <?php
/**
* This file is part of the Amacube-Remix_Quarantine Roundcube plugin
* Copyright (C) 2015, Tony VanGerpen <Tony.VanGerpen@hotmail.com>
* 
* A Roundcube plugin to let users release quarantined mail (which must be stored in a database)
* Based heavily on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
* 
* Licensed under the GNU General Public License version 3. 
* See the COPYING file in parent directory for a full license statement.
*/

class AmavisQuarantine
{
  # Database Settings
	private   $db_config;
  protected $db_conn;
  
  # AM.PDP Settings
  private $amavis_host = '';
  private $amavis_port = '';
  
  # Bayesian Filter Training
  private $bayes_train = false;
  private $bayes_ham_pipe = '';
  private $bayes_spam_pipe = '';
  
  # User Settings
  protected $user_email = '';
  
  // constructor
  function __construct( $db_config, $amavis_host, $amavis_port ) {
    $this->db_config = $db_config;
    
    $this->amavis_host = $amavis_host;
    $this->amavis_port = $amavis_port;
    
    $this->bayes_train = $bayes['train'];
    $this->bayes_ham_pipe = $bayes['ham_pipe'];
    $this->bayes_spam_pipe = $bayes['spam_pipe'];
    
    # Fetch Username
    $rcmail = rcmail::get_instance();
    $this->user_email = $rcmail->user->data['username'];
  }
  
  function init_db() {
    # Initialize Database Factory
    if ( !$this->db_conn ) {
      if ( !class_exists( 'rcube_db' ) ) // pre 0.9
        $this->db_conn = new rcube_mdb2( $this->db_config, '', TRUE );
      else // ver 0.9+
        $this->db_conn = rcube_db::factory( $this->db_config, '', TRUE );
    }
    
    # Connect to the Database
    $this->db_conn->db_connect('w');

    # Check DB connections and exit on failure
    if ( $err_str = $this->db_conn->is_error() ) {
      raise_error( array(
        'code' => 603,
        'type' => 'db',
        'message' => $err_str ), true, true );
    }
  }

  function db_error() {
    # Return the last database error message
    if( $this->db_conn && $this->db_conn->is_error() )
      return $this->db_conn->is_error();
      
    return false;
  }
  
  function fetch_list( $criteria ) {
    // Debug: Remove before production
    error_log( 'AMACUBE-REMIX: Fetch quarantined items.' );
    
    $errors = array();
    $items = array();
    
    # Validate Search Criteria
    if( !is_array( $criteria ) )
      $errors[] = "Invalid Search Criteria.  Array expected, " . gettype( $criteria ) . "received.\n";
    
    if( !is_resource( $this->db_conn ) )
      $this->init_db();
      
    if( count( $errors ) == 0 ) {
      $query = "
        SELECT
          msgs.time_num AS age, msgs.content AS content,
          bspam_level AS level, size,
          SUBSTRING(sender.email,1,40) AS sender,
          SUBSTRING(msgs.subject,1,40) AS subject,
          msgs.mail_id AS id
          FROM msgs LEFT JOIN msgrcpt              ON msgs.mail_id=msgrcpt.mail_id
                    LEFT JOIN maddr      AS sender ON msgs.sid=sender.id
                    LEFT JOIN maddr      AS recip  ON msgrcpt.rid=recip.id
                    LEFT JOIN quarantine AS quar   ON quar.mail_id = msgs.mail_id
          WHERE msgs.content IS NOT NULL 
          AND msgs.quar_type = 'Q'
          AND recip.email = ?";
          
      // Search Hook
      $number_of_times_to_inject_search_term = 0;
      if( !empty( $criteria['search_term'] ) ) {
        if( !$criteria['search_from'] && !$criteria['search_subject'] && !$criteria['search_body'] ) {
          $query .= " AND ( sender.email LIKE ? OR msgs.subject LIKE ? OR quar.mail_text LIKE ? )";
          $number_of_times_to_inject_search_term = 3;
        } else {
          if( $criteria['search_from'] ) {
            $query .= " AND sender.email LIKE ?";
            $number_of_times_to_inject_search_term++;
          }
            
          if( $criteria['search_subject'] ) {
            $query .= " AND msgs.subject LIKE ?";
            $number_of_times_to_inject_search_term++;
          }
            
          if( $criteria['search_body'] ) {
            $query .= " AND quar.mail_text LIKE ?";
            $number_of_times_to_inject_search_term++;
          }
        }
      }
      
      // Order By Hook
      // Also maps table col names to db field names
      switch( $criteria['sort_by'] ) {
        case 'sender':
          $query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY sender " . $criteria['sort_order'] : " ORDER BY sender DESC";
          break;
        case 'subject':
          $query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY subject " . $criteria['sort_order'] : " ORDER BY subject DESC";
          break;
        case 'date':
          $query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY age " . $criteria['sort_order'] : " ORDER BY age DESC";
          break;
        case 'type':
          $query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY content " . $criteria['sort_order'] : " ORDER BY content DESC";
          break;
        case 'score':
          $query .= ( isset( $criteria['sort_order'] ) && ( $criteria['sort_order'] == 'ASC' || $criteria['sort_order'] == 'DESC' ) ) ? " ORDER BY level " . $criteria['sort_order'] : " ORDER BY level DESC";
          break;
        default:
          $query .= " ORDER BY msgs.time_num DESC";
          break;
      }
      
      // This is STUPID!
      // Does anyone know a better way to enter dynamic PDO parameters?
      // Double the queries double the fun!
      // Run the query twice, so we can count the total number of messages for pagination.
      switch( $number_of_times_to_inject_search_term ) {
        case 1:
          $row_count = $this->db_conn->query( $query, $this->user_email, "%{$criteria['search_term']}%" )->rowCount();
          $results = $this->db_conn->limitquery( $query, ( ( ( $criteria['current_page'] -1 ) * $criteria['items_per_page'] ) ), $criteria['items_per_page'], $this->user_email, "%{$criteria['search_term']}%" );
          break;
        case 2:
          $row_count = $this->db_conn->query( $query, $this->user_email, "%{$criteria['search_term']}%", "%{$criteria['search_term']}%" )->rowCount();
          $results = $this->db_conn->limitquery( $query, ( ( ( $criteria['current_page'] -1 ) * $criteria['items_per_page'] ) ), $criteria['items_per_page'], $this->user_email, "%{$criteria['search_term']}%", "%{$criteria['search_term']}%" );
          break;
        case 3:
          $row_count = $this->db_conn->query( $query, $this->user_email, "%{$criteria['search_term']}%", "%{$criteria['search_term']}%", "%{$criteria['search_term']}%" )->rowCount();
          $results = $this->db_conn->limitquery( $query, ( ( ( $criteria['current_page'] -1 ) * $criteria['items_per_page'] ) ), $criteria['items_per_page'], $this->user_email, "%{$criteria['search_term']}%", "%{$criteria['search_term']}%", "%{$criteria['search_term']}%" );
          break;
        default:
          $row_count = $this->db_conn->query( $query, $this->user_email )->rowCount();
          $results = $this->db_conn->limitquery( $query, ( ( ( $criteria['current_page'] -1 ) * $criteria['items_per_page'] ) ), $criteria['items_per_page'], $this->user_email );
          break;
      }
      
      if( !empty( $this->db_error ) ) {
        $errors[] = $this->db_error;
        return "Error in selecting quarantined E-Mails: " . $this->db_error();
      }
        
      $result = array();
      while( $results && ( $result = $this->db_conn->fetch_assoc( $results ) ) )
        $items[] = $result;
    }
    
    # Return Errors and Items array
    $output = array( 'errors' => $errors, 'items' => $items, 'count' => $row_count );
    
    return $output;
  }
  
  function delete( $mail_id, $is_ham = false ) {
    // Debug: Remove before production
    error_log( 'AMACUBE-REMIX: delete mail with id: ' . $mail_id );
    
    $errors = array();
    
    if( !is_string( $mail_id ) )
      $errors[] = "Invalid Mail_Id: {$mail_id}\n";
    
    if( !is_resource( $this->db_conn ) )
      $this->init_db();
      
    if( count( $errors ) == 0 ) {
      # Check for mail id existence
      $query = 'SELECT `mail_id` FROM `msgs` WHERE `mail_id` = ?';
      
      $results = $this->db_conn->query( $query, $mail_id );
      
      if( !empty( $this->db_error ) )
        $errors[] = "Query Error ({$query}): " . $this->db_error() . "\n";
      elseif( $results->rowCount() < 1 )
        $errors[] = "No Message with Mail Id '{$mail_id}' exists in the quarantine.\n";
      
      if( count( $errors ) == 0 ) {
        # Train Bayes Before Deleting Message
        $this->train_bayes( $mail_id, $is_ham );
        
        # Build Queries - Delete records from 3 tables
        $queries = array();
        $queries[] = 'DELETE FROM `msgs` WHERE `mail_id` = ?';
        $queries[] = 'DELETE FROM `msgrcpt` WHERE `mail_id` = ?';
        $queries[] = 'DELETE FROM `quarantine` WHERE `mail_id` = ?';
        
        # Execute Each Query
        foreach( $queries AS $query ) {
          $this->db_conn->query( $query, $mail_id );
          
          if( !empty( $this->db_error ) )
            $errors[] = "Query Error ({$query})" . $this->db_error() . "\n";
        }
      }
    }
    
    if( count( $errors ) > 0 ) {
      foreach( $errors as $error )
        error_log( 'AMACUBE-REMIX: delete error: ' . $error );
        
      return $errors;
    }
    
    return false;
  }
  
  function train_bayes( $mail_id, $is_ham ) {
    if( $this->bayes_train ) {
      $message = '';
      
      $query = 'SELECT `mail_id` FROM `quarantine` WHERE `mail_id` = ? ORDER BY `partition_tag` ASC';
      $results = $this->db_conn->query( $query, $mail_id );
      
      while( $results && ( $result = $this->db_conn->fetch_assoc( $results ) ) )
        $message .= $result;
        
      if( !empty( $message ) ) {
        if( $is_ham )
          // exec_command: $this->bayes_ham_pipe
          error_log( 'AMACUBE-REMIX: train bayes (ham)' );
        else
          // exec_command: $this->bayes_spam_pipe
          error_log( 'AMACUBE-REMIX: train bayes (spam)' );
      }
    }
      
    return;
  }
  
  function release( $mail_id ) {
    // Debug: Remove before production
    error_log( 'AMACUBE-REMIX: release mail with id: ' . $mail_id );
    
    $errors = array();
    $release_error_ids = array();
    $release_success_ids = array();
    
    if( !is_string( $mail_id ) )
      $errors[] = "Invalid Mail_Id: {$mail_id}\n";
    
    if( !is_resource( $this->db_conn ) )
      $this->init_db();
      
    if( count( $errors ) == 0 ) {
      # Check for mail id existence
      $query = 'SELECT `partition_tag`, `mail_id`, `secret_id`, `quar_type` FROM `msgs` WHERE `mail_id` = ?';
      
      $results = $this->db_conn->query( $query, $mail_id );
      
      if( !empty( $this->db_error ) )
        $errors[] = "Query Error ({$query}): " . $this->db_error() . "\n";
      elseif( $results->rowCount() < 1 )
        $errors[] = "No Message with Mail Id '{$mail_id}' exists in the quarantine.\n";
        
      if( count( $errors ) == 0 ) {
        $commands = array();
        while( $results && ( $result = $this->db_conn->fetch_assoc( $results ) ) ) {
          $command  = '';
          $command .= "request=release\r\n";
          $command .= "mail_id={$result['mail_id']}\r\n";
          $command .= "secret_id={$result['secret_id']}\r\n";
          $command .= "partition_tag={$result['partition_tag']}\r\n";
          $command .= "quar_type={$result['quar_type']}\r\n";
          $command .= "requested_by=" . $this->user_email . "%20via%20amacube\r\n";
          $command .= "\r\n";
          $commands[$result['mail_id']] = $command;
        }
        
        error_log("AMACUBE-REMIX: command array: " . implode( ',', str_replace( "\r\n", "_CR_NL_", $commands ) ) );
        
        // open socket to amavis process and send release commands:
        $fp = fsockopen( $this->amavis_host, $this->amavis_port, $errno, $errstr, 5 );
        
        if( $fp ) {
          stream_set_timeout( $fp, 5 );
          
          foreach( $commands as $command_id => $command ) {
            if( fwrite( $fp, $command ) ) {
              $answer = 'New answer after ' . $command;
              
              while( !feof( $fp ) ) {
                $response = fgets( $fp );
                $answer .= $response;
                
                if( substr( $response, 0, 12 ) === 'setreply=250' ) {
                  // save success result in commands array
                  array_push( $release_success_ids, $command_id);
                } elseif( $response == "\r\n" ) {
                  // server answered, and waits for more commands
                  break;
                } else {
                  // error response
                  if( !empty( $response ) )
                    $release_error_ids[$command_id] = $response;
                }
              }
            } else {
              $errors[] = "Failed to write to socket\n";
              error_log("AMACUBE-REMIX: release: write to socket failed\n");
            }
            
            error_log( "AMACUBE-REMIX: amavis said: " . str_replace( "\r\n", "_CR_NL_", $answer ) );
          }
          
          fclose( $fp );
        } else {
          $errors[] = "Failed to open socket: {$errstr} ({$errno})\n";
          error_log("AMACUBE-REMIX: socket open failed: $errstr ($errno)\n");
        }
            
        // successfully released emails can be deleted
        foreach( $release_success_ids AS $released_id ) {
          # Discard Message
          $results = $this->delete( $released_id, true );
          
          # Display/Log Discard Errors
          if( is_array( $results ) )
            foreach( $results AS $result )
              $errors[] = $result;
        }
        
        if( count( $release_error_ids ) > 0) {
          foreach( $release_error_ids AS $mail_id ) {
            $errors[] = "After successful releasing, deletion of the message with Mail_Id '{$mail_id}' from the quarantine failed.";
            error_log("AMACUBE-REMIX: Sucessful release but failed deletion for Mail_Id '{$mail_id}'");
          }
        }
        
        if( count( $errors ) > 0 ) {
          foreach( $errors as $error )
            error_log( 'AMACUBE-REMIX: delete error: ' . $error );
            
          return $errors;
        }
      }
    }
    
    return false;
  }
}
?>