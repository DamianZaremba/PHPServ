<?php
/*
 * PHPServBot.php created on Oct 19, 2010 by Josh "SnoFox" Johnson
 *
 */
 
	class PHPServBot {
		protected $id;
		protected $nick;
		protected $user;
		protected $host;
		protected $gecos;
		
		public static function create($module, $nick, $user, $host, $gecos) {
			$newId = Database::insert(
				'bots',
				Array(
					'id'	=> 'NULL',
					'nick'	=> $nick,
					'ident' => $user,
					'host'	=> $host,
					'gecos'	=> $gecos,
					'module' => $module
				)
			);
			
			if( $newId === false )
				throw new Exception( "Database insert failed." );
				
			return self::newFromId( $newId );
		}
		
		public static function newFromId( $id ) {
			return self::newFrom( 'id', $id );
		}
		
		public static function newFromNick( $nick ) {
			return self::newFrom( 'nick', $nick );
		}
		
		protected static function newFrom( $field, $value ) {
			$botData = Database::get( Database::sql( 'SELECT * FROM `bots` WHERE `' . $field . '` = ' . Database::escape( $value ) . ' LIMIT 1' ) );
			
			if (isset($botData)) {
				return new self(
					$botData['id'],
					$botData['nick'],
					$botData['ident'],
					$botData['host'],
					$botData['gecos'],
					Module::newFromName( $botData['module'] )
					);
			}
			
			return null;
		}
		
		protected function __construct($id, $nick, $ident, $host, $gecos, $module) {
			$this->id = $id;
			$this->nick = $nick;
			$this->ident = $ident;
			$this->host = $host;
			$this->gecos = $gecos;
			$this->module = $module;
		}
		
		public function __get( $property ) {
			switch ($property) {
				case 'id':
				case 'nick':
				case 'ident':
				case 'host':
				case 'gecos':
				case 'module':
					return $this->$property;
				default:
					throw new Exception( 'No such property: ' . $property );
			}
		}
		
		public function __set( $property, $value ) {
			switch ($property) {
				case 'id':
				case 'module':
					throw new Exception('Cannot set ' . $property . ' property!');
					break;
				case 'nick':
				case 'ident':
				case 'host':
				case 'gecos':
					$this->update( $property, $value );
					break;
				default:
					throw new Exception( 'Unknown property: ' . $property );
			}
		}
		
		public function update( $property, $value) {
			Database::sql( 'UPDATE `bots` SET `' . $property . '` = ' . Database::escape( $value ) . ' WHERE `id` = ' . Database::escape( $this->id ) );
			$this->$property = $value;
		}
}
?>
