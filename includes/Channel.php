<?PHP
	class Channel {
		protected $id; // channel ID
		protected $name; // channel name
		protected $modes; // simple modes
		protected $topic; // topic stuff
		
		public static function newFromId( $id ) {
			return self::newFrom( 'id', $id );
		}
		
		public static function newFromName( $name ) {
			return self::newFrom( 'name', $name );
		}
		
		protected static function newFrom( $field, $value ) {
			$chanData = Database::get( Database::sql( 'SELECT * FROM `channels` WHERE ' . $field . ' = ' . Database::escape( $value ) ) );
			
			if( $chanData ) {
				return new self(
					$chanData[ 'chanid' ],
					$chanData[ 'name' ],
					$chanData[ 'topic' ],
					$chanData[ 'modes' ]
				);
			}
			
			return null;
		}
		
		protected function __construct( $id, $name, $modes, $topic ) {
			$this->id = $id;
			$this->name = $name;
			$this->modes = $modes;
			$this->topic = $topic;
		}
		
		public function __set( $name, $value ) {
			switch( $name ) {
				case 'id':
				case 'name':
					throw new Exception( 'Cannot set ' . $name . ' property' );
					break;
				case 'modes':
				case 'topic':
					$this->update( $name, $value );
					break;
				default:
					throw new Exception( 'Unknown property.' );
			}
			
		}
		
		public function __get( $name ) {
			switch( $name ) {
				case 'id':
					return $this->id;
				case 'name':
					return $this->name;
				case 'modes':
					return $this->modes;
				case 'topic':
					return $this->topic;
				default:
					return null;
			}
		}
		
		public function create( $name, $modes, $topic) {
			$newId = Database::insert(
				'channels',
				array(
					'name' => $name,
					'modes' => $modes,
					'topic' => $topic
				)
			);
			if( $newId === false)
				throw new Exception( "Database insert failed.");

			return self::newFromId( $newId );
		}

		protected function getUsers() {
			return ChannelUsers::newFromChannel( $this );
		}
					
		protected function update( $name, $value ) {
			Database::sql( 'UPDATE `channels` SET `' . $name . '` = ' . Database::escape( $value ) . ' WHERE `id` = ' . Database::escape( $this->id ) );
			$this->$name = $value;
		}
	}
?>
