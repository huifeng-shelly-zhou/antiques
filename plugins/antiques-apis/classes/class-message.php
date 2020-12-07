<?php
global $antiquea_message;
$antiquea_message = new ANTIQUES_MESSAGE();

class Message_Actions
{
	const read = 'READ';
	const unread = 'UNREAD';
	const removed = 'REMOVED'; 	
}

class ANTIQUES_MESSAGE
{	
	
	private $table_name = 'antique_messages';
	
	public function __construct(){ 
	
		$this->createTable();
		
		add_action( 'admin_menu', array($this, 'add_admin_menu') );
    }
	
	/**
	 * Get table columns.
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {

		return array(
			'id'      			=> '%d',
			'message_key'       => '%s',
			'antique_id'       	=> '%d',
			'owner_id'       	=> '%d',
			'owner_name'       	=> '%s',
			'client_id'       	=> '%d',
			'client_name'       => '%s',
			'antique_title'     => '%s',
			'antique_image'   	=> '%s',
			'antique_lang'      => '%s',
			'sender_id'         => '%d',
			'sender_name'       => '%s',
			'created_time' 		=> '%s',
			'content'    		=> '%s',
			'owner_action'    	=> '%s',
			'client_action'    	=> '%s',
		);
	}
	
	/**
	 * Default column values.
	 *
	 * @since 1.1.6
	 */
	public function get_column_defaults() {

		return array(
			'message_key'       => '',
			'antique_id'       	=> '',
			'owner_id'       	=> '',
			'owner_name'       	=> '',
			'client_id'       	=> '',
			'client_name'       => '',
			'antique_title'     => '',
			'antique_image'   	=> '',
			'antique_lang'      => '',
			'sender_id'         => '',
			'sender_name'       => '',
			'created_time' 		=> '',
			'content'    		=> '',
			'owner_action'    	=> '',
			'client_action'    	=> '',
		);
	}
	
	/**
	 * Check if the given table exists.
	 *
	 * @param  string $table The table name.
	 *
	 * @return string|null If the table name exists.
	 */
	private function table_exists() {

		global $wpdb;		

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) ) === $this->table_name;
	}

	private function createTable(){
		global $wpdb;	
		
		if ($this->table_exists()){
			//$wpdb->query( "DROP TABLE $this->table_name;" );	
			return true;
		}
		
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
		
		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			message_key varchar(50) NOT NULL,
			antique_id bigint(20) NOT NULL,
			owner_id bigint(20) NOT NULL,
			owner_name varchar(30) NOT NULL,
			client_id bigint(20) NOT NULL,
			client_name varchar(30) NOT NULL,
			antique_title text NOT NULL,
			antique_image varchar(255) NOT NULL,
			antique_lang varchar(10) NOT NULL,
			sender_id bigint(20) NOT NULL,
			sender_name varchar(30) NOT NULL,
			created_time varchar(30) NOT NULL,
			content text NOT NULL,
			owner_action varchar(10) NOT NULL DEFAULT 'UNREAD',
			client_action varchar(10) NOT NULL DEFAULT 'UNREAD',
			PRIMARY KEY  (id)			
		) {$charset_collate};";
		
		$wpdb->query($sql);
		
		if ($this->table_exists()){
			return true;
		}
		else{
			return false;
		}
	}
	

	public function remove($row_ids){
		global $wpdb;
		
		if (is_string($row_ids) && !empty($row_ids)){		
			
			return $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE id in (".$row_ids.");" ) );			
		}

		return false;		
	}
	
	public function updateAction($message_keys, $user_id, $value){
		global $wpdb;
		
		$result = array();
		
		if (!is_array($message_keys) || !is_numeric($user_id) || $user_id <= 0 || empty($value)){
			return false;	
		}
		
		foreach($message_keys as $key){
			$result[$key] = false;
			
			$list = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE message_key='" . $key . "' LIMIT 1;");
			if ($list && count ($list) > 0){
				
				if(isset($list[0]->owner_id) && $user_id == $list[0]->owner_id){
					
					$result[$key] = $wpdb->query( $wpdb->prepare( "UPDATE $this->table_name SET owner_action = %s WHERE message_key = %s AND owner_action <> %s", $value, $key, Message_Actions::removed) );
				}
				else if(isset($list[0]->client_id) && $user_id == $list[0]->client_id){
					
					$result[$key] = $wpdb->query( $wpdb->prepare( "UPDATE $this->table_name SET client_action = %s WHERE message_key = %s AND client_action <> %s", $value, $key, Message_Actions::removed) );
				}
			}
		}
		
		return $result;		
	}
	
	
	public function insert($data){
		global $wpdb;
		
		// Set default values.
		$data = wp_parse_args( $data, $this->get_column_defaults() );
		
		// Initialise column format array.
		$column_formats = $this->get_columns();
		
		// Force fields to lower case.
		$data = array_change_key_case( $data );
		
		// White list columns.
		$data = array_intersect_key( $data, $column_formats );
		
		// Reorder $column_formats to match the order of columns given in $data.
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );
		
		return $wpdb->insert_id;
	}
	
	
	public function get_by_user($user_id){
		global $wpdb;		

		if (is_numeric($user_id) && $user_id > 0){
		
			return $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE owner_id = " . $user_id . " OR client_id = " . $user_id . ";");			
		}
		
		return array();		
	}
	
	
	public function get_by_key($key){
		global $wpdb;		
		
		if (is_string($key) && !empty($key)){	
			
			return $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE message_key='" . $key . "';");			
		}
		
		return array();		
	}
	
	
	public function get_all(){
		global $wpdb;
		
		if ($this->table_exists()){
			return $wpdb->get_results(			
					"SELECT * FROM {$this->table_name};"
			);
		}
		else{
			return null;
		}		
	}
	
	
	public function filteOutRemovedMessage($user_id, $list){
		
		if(!isset($user_id) || !is_array($list)){
			return $list;
		}
		
		global $removed_user_id, $removed_action;
		$removed_user_id = $user_id;
		$removed_action = Message_Actions::removed;
		// filter out action is REMOVED message		
		$filter_list = array_filter($list, function ($item) {
			global $removed_user_id, $removed_action;
			
			if (isset($item->owner_id) && isset($item->owner_action) && $item->owner_id == $removed_user_id && $item->owner_action != $removed_action){
				return true;
			}
			else if (isset($item->client_id) && isset($item->client_action) && $item->client_id == $removed_user_id && $item->client_action != $removed_action){
				return true;
			}
			else{
				return false;
			}				
		});
		
		return $filter_list;
	}
	
	
	public function defineUnreadMessages($user_id, $list){
		
		if(!isset($user_id) || !is_array($list)){
			return $list;
		}
		
		global $defind_user_id;
		$defind_user_id = $user_id;
		
		return array_map(function ($item) {	
			global $defind_user_id;
			
			$unread = false;
			if (isset($item->owner_id) && isset($item->owner_action) && $item->owner_id == $defind_user_id && $item->owner_action == Message_Actions::unread){
				$unread = true;
			}
			else if (isset($item->client_id) && isset($item->client_action) && $item->client_id == $defind_user_id && $item->client_action == Message_Actions::unread){
				$unread = true;
			}
			
			$item = (array)$item;
			unset($item['owner_action']);
			unset($item['client_action']);
			$item['unread'] = $unread;
			
			return (object) $item;	
		}, $list);
		
		
	}
	
	
	public function add_admin_menu()
	{
		add_menu_page(
			'Antique Messages', 					// page title
			'Messages', 							// menu title
			'edit_dashboard',						// capability
			'antique-messages-dashboard',			// menu_slug
			array($this, 'render_messages')			// function
		);		
	}
	
	public function render_messages(){
		
		$messages_items = $this->get_all();
		$columns = $this->get_column_defaults();
		$messages = array();
		foreach($messages_items as $item){
			
			if (!isset($messages[$item->message_key])){
				$messages[$item->message_key] = array();
			}
			
			$messages[$item->message_key][] = $item;
		}
		
		?>
		<style>
		table, th, td {
		  border: 1px solid black;
		  border-collapse: collapse;
		}
		th, td {
		  padding-left: 10px !important;		    
		}
		</style>
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		<table class="form-table">
			 <thead>
                <tr>
				<?php foreach ($columns as $key=>$value) { ?>
                    <th><?php echo ucwords(str_replace('_', ' ', $key)); ?></th>         
				<?php } ?>
                </tr>
            </thead>
		<?php
		
		if ($messages){ ?>
			<tbody>			
			<?php foreach ($messages as $message_items){ ?>
				
				<?php foreach ($message_items as $index=>$row){ ?>
				
				<tr>					
					<?php if ($index == 0) { ?>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->message_key ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->antique_id ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->owner_id ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->owner_name ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->client_id ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->client_name ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->antique_title ?></td>
					<td rowspan="<?php echo count($message_items) ?>"><img src="<?php echo $row->antique_image ?>" style="width:80px;height:80px;" /></td>
					<td rowspan="<?php echo count($message_items) ?>"><?php echo $row->antique_lang ?></td>
					<?php } ?>
					<td><?php echo $row->sender_id ?></td>
					<td><?php echo $row->sender_name ?></td>
					<td><?php echo $row->created_time ?></td>
					<td><?php echo $row->content ?></td>
					<td><?php echo $row->owner_action ?></td>
					<td><?php echo $row->client_action ?></td>
				</tr>
				
				<?php } ?>	
				
			<?php } ?>			
			</tbody>
		<?php } ?>
		
		</table>
		<?php
	}
}

?>