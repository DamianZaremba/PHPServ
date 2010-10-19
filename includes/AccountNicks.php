<?php
	class AccountNicks {
		protected $account;
		protected $users;
		
		public static function newFromAccount( $account ) {
			$users = Array();
			$result = Database::sql( 'SELECT `id` FROM `users` WHERE `loggedin` = ' . Database::escape( $account->id ) );
			while( $row = Database::get( $result ) )
				$users[] = User::newFromId( $row[ 'id' ] );
			return new self(
				$account,
				$users
			);
		}
		
		protected function __construct( $account, $users ) {
			$this->account = $account;
			$this->users = $users;
		}
		
		public function __get( $name ) {
			switch( $name ) {
				case 'account':
					return $this->account;
				case 'asArray':
					return $this->users;
				default:
					throw new Exception( 'Unknown property: ' . $name );
			}
		}
	}
?>