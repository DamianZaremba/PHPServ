<?php
	class AccountProperty {
		protected $account;
		protected $key;
		protected $value;
		protected $visibility;
		protected $section;
		
		public static function create( $account, $key, $value, $visibility = 'hidden', $section = 'core' ) {
			$data = array( 
				'uid' => $account->id,
				'key' => $key,
				'value' => $value,
				'visibility' => $visibility,
				'section' => $section 
			);
			Database::insert( 'access_properties', $data, true );
			return self::newFromAccountSectionKey( $account, $section, $key );
		}
		
		public static function remove( $property ) {
			Database::sql( 'DELETE FROM `access_properties` WHERE `id` = ' . Database::escape( $property->id ) );
		}
		
		public static function newFromAccountSectionKey( $account, $section, $key ) {
			$data = Database::get(
				Database::sql(
					'SELECT * '
					. 'FROM `access_properties` '
					. 'WHERE `uid` = ' . Database::escape( $account->id )
					. ' AND `key` = ' . Database::escape( $key )
					. ' AND `section` = ' . Database::escape( $section )
				)
			);
			return new self(
				$account,
				$data[ 'key' ],
				$data[ 'value' ],
				$data[ 'visibility' ],
				$data[ 'section' ]
			);
		}
		
		public function __construct( $account, $key, $value, $visibility, $section ) {
			$this->account = $account;
			$this->key = $key;
			$this->value = $value;
			$this->visibility = $visibility;
			$this->section = $section;
		}
		
		public function __get( $name ) {
			switch( $name ) {
				case 'account':
				case 'key':
				case 'value':
				case 'visibility':
				case 'section':
					return $this->$name;
				default:
					throw new Exception( 'No such property.' );
			}
		}
		
		public function __set( $name, $value ) {
			switch( $name ) {
				case 'account':
				case 'key':
				case 'section':
					throw new Exception( 'Can not change property: ' . $name );
				case 'value':
				case 'visibility':
					$this->update( $name, $value );
					break;
				default:
					throw new Exception( 'Unknown property.' );
			}
		}
		
		protected function update( $name, $value ) {
			Database::sql(
				'UPDATE `access_properties` '
				. 'SET `' . $name . '` = ' . Database::escape( $value )
				. ' WHERE `uid` = ' . Database::escape( $this->account->id )
				. ' AND `key` = ' . Database::escape( $this->key )
				. ' AND `section` = ' . Database::escape( $this->section )
			);
			$this->$name = $value;
		}
	}
?>