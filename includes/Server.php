<?PHP
	class Server {
		protected $id; // Server ID
		protected $name; // Server's hostname
		protected $gecos; // Server's description
		
		public static function newFromName( $servName ) {
			return self::newFrom( 'name', $servName );
		}
		
		public static function newFromId( $serverId ) {
			return self::newFrom( 'servid', $serverId );
		}
		
		protected static function newFrom( $field, $value ) {
			$servData = Database::get( Database::sql( 'SELECT * FROM `servers` WHERE ' . $field . ' = ' . Database::escape( $value ) ) );
			
			if( $servData ) {
				return new self(
					$servData[ 'servid' ],
					$servData[ 'name' ],
					$servData[ 'description' ]
				);
			}
			return null;
		}
		
		protected function __construct( $serverId, $servName, $servDesc ) {
			$this->id = $serverId;
			$this->name = $servName;
			$this->gecos = $servDesc;
		}
		
		public function __get( $field ) {
			switch( $field ) {
				case 'id':
					return $this->id;
				case 'name':
					return $this->name;
				case 'gecos':
					return $this->gecos;
				default:
					return null;
			}
		}
		
		public static function create( $servName, $servDesc ) {
			$newId = Database::insert(
				'servers',
					array( 'name' => $servName,
					'description' => $servDesc
				)
			);
			
			if ( $newId === false )
				throw new Exception( "Database insert failed." );
			
			return self::newFromId( $newId );
		}
		
		public static function remove( $server ) {
			Database::sql( 'DELETE FROM `servers` WHERE `servid` = ' . $server->id );
		}
	}
	
	
?>
