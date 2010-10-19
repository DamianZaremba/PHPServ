<?php
	class AccountProperties {
		protected $account;
		
		public static function newFromAccount( $account ) {
			return new self( $account );
		}
		
		protected function __construct( $account ) {
			$this->account = $account;
		}
	
		public function search( $key = null, $value = null, $visibility = null, $section = null ) {
			
			$sql = 'SELECT `id` FROM `access_properties` WHERE';
			
			$sql .= ' `uid` = ' . Database::escape( $this->account->id );
			
			if( $key !== null )
				$sql .= ' AND `key` = ' . Database::escape( $key );
			
			if( $value !== null )
				$sql .= ' AND `value` = ' . Database::escape( $value );
			
			if( $visibility !== null )
				$sql .= ' AND `visibility` = ' . Database::escape( $visibility );
			
			if( $section !== null )
				$sql .= ' AND `section` = ' . Database::escape( $section );
			
			$result = Database::sql( $sql );
			$results = Array();
			
			while( $row = Database::get( $result ) )
				$results[] = AccountProperty::newFromId( $row[ 'id' ] );
			
			return $results;
		}
	
		public function get( $key, $section = 'core' ) {
			$data = $this->listaccountproperties( $key, null, null, $section );
			return $data[ 0 ];
		}
	}
?>