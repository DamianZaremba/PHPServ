<?PHP

class Unreal {

	private static $connected;

	function event_raw_in( $data ) {
		
		$data = str_replace( array( "\n", "\r" ), '', $data );
		
		$data = IRC::split( $data );
		
		if( $data[ 'type' ] == 'direct' )
			switch( $data[ 'command' ] ) {
				
				case 'ping':
					$this->raw( 'PONG :' . $data[ 'pieces' ][ 0 ] );
					break;
					
				case 'nick':
					// NICK scrawl85 1 1287461713 ~scrawl data.searchirc.org theta.cluenet.org 0 +iw * SECRFA== :SearchIRC Crawler
					// NICK <nick=0> * <timestamp=2> <user=3> <host=4> <server=5> <stamp=6> <modes=7> * <ip=9> <real=10>
					$binaryIP = base64_decode( $data[ 'pieces' ][ 9 ] );
					if( strlen( $binaryIP ) > 4 ) {
						// IPv6
						$ipParts = Array();
						for( $i = 0 ; $i < strlen( $binaryIP ) ; $i += 2 )
							$ipParts[] = dechex( ord( $binaryIP[ $i ] ) << 8 | ord( $binaryIP[ $i + 1 ] ) );
						$decodedIP = implode( ':', $ipParts ); 
					} else // IPv4
						$decodedIP = ord( $binaryIP[ 0 ] ) . '.' . ord( $binaryIP[ 1 ] )
							. '.' . ord( $binaryIP[ 2 ] ) . '.' . ord( $binaryIP[ 3 ] );
					
					$user = User::create(
						$data[ 'pieces' ][ 0 ], // Nick
						$data[ 'pieces' ][ 3 ], // User
						$data[ 'pieces' ][ 4 ], // Hostname
						$data[ 'pieces' ][ 10 ],// Real name
						$data[ 'pieces' ][ 7 ], // Modes
						$decodedIP, // IP
						Server::newFromName( $data[ 'pieces' ][ 5 ] )
					);
					
					if( $data[ 'pieces' ][ 6 ] != 0 )
						$user->account = Account::newFromId( $data[ 'pieces' ][ 6 ] );

					event( 'signon', $user );
					break;
	
				case 'eos':
					event( 'eos' );
					break;
			}
		else if( $data[ 'type' ] == 'relayed' )
			// There's probably a better way to do this...
			// Two Database lookups for a server-source? --SnoFox
			// Three if you count PHPserv bot sources! :\
			// But I figured something above the switch would be better
			//   than searching for the source every case...
			//   Maybe it wouldn't be that bad, though...
			$src = explode( '!', $data[ 'source' ], 1 );
			
			$user = User::newFromNick( $src );
			
			if( $user === null)
				$user = PHPServBot::newFromName( $src );
				
			if( $user === null)
				$user = Server::newFromName( $src );
				
			if( $user === null )
				logit('Got ' . $data[ 'command' ] . 'command from unknown source ' . $data[ 'source' ] );
			// End search for better ways
			
			switch( $data[ 'command' ] ) {
				// "Docs" provided here are wrong ^.^
				case 'nick':
				case 'svsnick':
				// Emit event: nick, User $user, string $newNick
				event( 'nick', $user, $data[ 'target' ] );
					break;
	
				case 'quit':
				// Emit event: quit, User $user, string $reason
				event( 'quit', $user, $data[ 'pieces' ] );
					break;
	
				case 'join':
				// Emit event: join, User $user, Channel $channel
				$channel = Channel::newFromName( $data[ 'target' ] );
				event( 'join', $user, $channel );
					break;
	
				case 'part':
				// Emit event: part, User $user, Channel $channel, string $reason
				$channel = Channel::newFromName( $data[ 'target' ] );
				event( 'part', $user, $channel, $data[ 'pieces' ] );
					break;
	
				case 'svskill':
				case 'kill':
				// Emit event: kill, User $src, $pwntUser, string $reason
				$target = User::newFromName( $data[ 'target' ] );
				if( $target === null )
					$target = PHPServBot::newFromName( $data[ 'target' ] );
				event( 'kill', $user, $target, $data[ 'pieces'] );
					break;
	
				case 'mode':
				// XXX: Unparsed mode event --SnoFox
				event( 'mode', $user, $data[ 'target' ], $data[ 'pieces' ] );
					break;
	
				case 'invite':
				event( 'invite', $user, $data[ 'target' ] );
					break;
	
				case 'privmsg':
				if( $data[ 'target' ][ 0 ] == '#' ) {
					$channel = Channel::newFromName( $data[ 'target' ] );
					event( 'chanmsg', $user, $channel, $data[ 'pieces' ] );
				} else {
					$target = PHPServBot::newFromName( $data[ 'target' ] );
					event( 'privmsg', $user, $target, $data[ 'pieces' ] );
				}
					break;
	
				case 'notice':
				if( $data[ 'target' ][ 0 ] == '#' ) {
					$channel = Channel::newFromName( $data[ 'target' ] );
					event( 'channotice', $user, $channel, $data[ 'pieces' ] );
				} else {
					$target = PHPServBot::newFromName( $data[ 'target' ] );
					event( 'notice', $user, $target, $data[ 'pieces' ] );
				}
					break;
	
				case 'kick':
					$target = User::newFromNick( $data[ 'target' ] );
					if( $target === null )
						$target = PHPServBot::newFromName( $data[ 'target' ] );
						
				event( 'kick', $data[ 'target' ], $target, $data[ 'pieces' ] );
					break;
	
				case 'topic':
				$channel = Channel::newFromName( $data[ 'target' ] );
				event( 'topic', $user, $channel. $data[ 'pieces' ] );
					break;
			}
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//// Old stuff                                                                                                           ////
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if( @strtolower( $dataParts[ 0 ] ) == "ping" ) {
			$this->raw( 'PONG ' . $dataParts[ 1 ] );
		
		} elseif( @strtolower( $dataParts[ 0 ] ) == "nick" ) {
			// event('signon', $d_a[1], $d_a[2], $d_a[3], $d_a[4], $d_a[5], $d_a[6], $d_a[7], substr(implode(array_slice($d_a, 8), " "), 1));
			// NICK gnarfel 1 1135469324 iseedp2 cpe-24-58-228-168.twcny.res.rr.com powerplace.ath.cx 0 +iwx * :Anthony F
			$a = preg_split( '//', base64_decode( $dataParts[ 10 ] ), -1, PREG_SPLIT_NO_EMPTY );
			foreach( $a as $y => $x ) {
				$a[ $y ] = ord( $x );
			}
			$msg = implode( '.', $a );
			event( 'signon', $dataParts[ 1 ], $dataParts[ 4 ], $dataParts[ 5 ], substr( implode( array_slice( $dataParts, 11 ), " " ), 1 ), $msg, $dataParts[ 6 ], $dataParts[ 7 ] );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "nick" ) {
			event( 'nick', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ] );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "svsnick" ) {
			event( 'nick', $dataParts[ 2 ], $dataParts[ 3 ] );
			
		//			} elseif (@strtolower($d_a[1]) == "server") {
		//				event('server', 
		} elseif( @strtolower( $dataParts[ 1 ] ) == "quit" ) {
			event( 'quit', substr( $dataParts[ 0 ], 1 ), substr( implode( ' ', array_slice( $dataParts, 2 ) ), 1 ) );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "join" ) {
			$c = explode( ',', $dataParts[ 2 ] );
			foreach( $c as $y ) {
				event( 'join', substr( $dataParts[ 0 ], 1 ), $y );
			}
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "part" ) {
			event( 'part', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ) );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "kill" ) {
			// Emit event: nick, $src, $pwntUser, $reason
			event( 'kill', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ) );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "svskill" ) {
			event( 'svskill', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ) );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "mode" ) {
			$modeline = implode( ' ', array_slice( $dataParts, 3 ) );
			if( $modeline{ 0 } == ':' )
				$modeline = substr( $modeline, 1 );
			$from = substr( $dataParts[ 0 ], 1 );
			$to = $dataParts[ 2 ];
			$modes = $dataParts[ 3 ];
			if( $modes{ 0 } == ':' )
				$modes = substr( $modes, 1 );
			
			event( 'mode', $from, $to, $modeline );
			
			if( $to{ 0 } == '#' )
				event( 'chanmode', $from, $to, $modeline );
			else
				event( 'usermode', $from, $to, $modeline );
			
			$i = 4;
			$t = '+';
			for( $j = 0 ; $j < strlen( $modes ) ; $j++ ) {
				if( $to{ 0 } == '#' ) { // Channels
					switch( $modes{ $j } ) {
						case '+':
							$t = '+';
							break;
						case '-':
							$t = '-';
							break;
						case 'q': // Owner
						case 'a': // Chanadmin
						case 'o': // Op
						case 'h': // Halfop
						case 'v': // Voice
						case 'b': // Ban
						case 'e': // Ban exception
						case 'I': // Invite exception
						case 'k': // Keyed
						case 'f': // Anti-flood
						case 'L': // Limit redirect
						case 'l': // Limit
						case 'j': // Join throttle
						// Spams #services when modes are set without params. Possible with /mode # -k (and likes)
							event( 'chanmode_' . $modes{ $j }, $from, $to, $t, $dataParts[ $i ] );
							$i++;
							break;
						default:
							event( 'chanmode_' . $modes{ $j }, $from, $to, $t );
							break;
					}
				} else { // Users
					switch( $modes{ $j } ) {
						case '+':
							$t = '+';
							break;
						case '-':
							$t = '-';
							break;
						default:
							event( 'usermode_' . $modes{ $j }, $from, $to, $t );
							break;
					}
				}
			}
			unset( $from, $to, $modes, $modeline, $i, $j, $t );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "invite" ) {
			//:Cobi INVITE Katelin :#fun
			// Emit event: invite, $src $invitedUser $chan
			event( 'invite', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( $dataParts[ 3 ], 1 ) );
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "privmsg" ) {
			if( ( $dataParts[ 3 ]{ 1 } == chr( 1 ) ) and ( substr( implode( ' ', array_slice( $dataParts, 3 ) ), -1, 1 ) == chr( 1 ) ) ) {
				$reply = substr( substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ), 1, -1 );
				$reply = explode( ' ', $reply, 2 );
				$type = $reply[ 0 ];
				if( !isset( $reply[ 1 ] ) ) {
					$reply = '';
				} else {
					$reply = $reply[ 1 ];
				}
				event( 'ctcp', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], $type, $reply );
			} else {
				event( 'msg', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ) );
			}
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "notice" ) {
			if( ( $dataParts[ 3 ]{ 1 } == chr( 1 ) ) and ( substr( implode( ' ', array_slice( $dataParts, 3 ) ), -1, 1 ) == chr( 1 ) ) ) {
				$reply = substr( substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ), 1, -1 );
				$reply = explode( ' ', $reply, 2 );
				$type = $reply[ 0 ];
				$reply = $reply[ 1 ];
				if( !isset( $reply ) ) {
					$reply = '';
				}
				event( 'ctcpreply', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], $type, $reply );
			} else {
				event( 'notice', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 3 ) ), 1 ) );
			}
		
		} elseif( @strtolower( $dataParts[ 1 ] ) == "eos" ) {
			global $connected;
			$this->raw( 'TSCTL SVSTIME ' . time() );
			if( !$connected ) {
				$connected = true;
				global $aml3;
				$aml3 = 1;
				$this->smo( 'o', "\002(\002Burst\002)\002 [" . Database::getsetting( 'server' ) . "] End of Incomming NetBurst." );
				event( 'eos', substr( $dataParts[ 0 ], 1 ) );
			}
		} elseif( @strtolower( $dataParts[ 1 ] ) == "kick" ) {
			//:source KICK #channel user :reason
			// Emit event: kick, $src $pwntUser $channel $reason
			event( 'kick', substr( $dataParts[ 0 ], 1 ), $dataParts[ 3 ], $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 4 ) ), 1 ) );
		} elseif( @strtolower( $dataParts[ 1 ] ) == "topic" ) {
			//:source TOPIC #channel nick timestamp :topic
			// Emit event: topic, $nick $chan $newtopic
			event( 'topic', substr( $dataParts[ 0 ], 1 ), $dataParts[ 2 ], substr( implode( ' ', array_slice( $dataParts, 5 ) ), 1 ) );
		}
	}

	function event_connected() {
		$this->raw( 'PASS ' . Database::getsetting( 'pass' ) );
		$this->raw( 'PROTOCTL NICKv2 NICKIP' );
		$this->raw( 'SERVER ' . Database::getsetting( 'server' ) . ' ' . Database::getsetting( 'numeric' ) . ' :' . Database::getsetting( 'desc' ) );
	}

	function raw( $data ) {
		global $sock;
		$sock->write( str_replace( "\r", '', $data ) ); /* We should never be sending \r. */
	}

	function addserv( $name , $desc ) {
		$this->raw( ':' . Database::getsetting( 'server' ) . ' SERVER ' . $name . ' 2 :' . $desc );
	}

	function smo( $mode , $message ) {
		$this->raw( ':' . Database::getsetting( 'server' ) . ' SMO ' . $mode . ' :' . $message );
	}

	function addserv2serv( $new , $old , $desc ) {
		$this->raw( ':' . $old . ' SERVER ' . $new . ' 3 :' . $desc );
	}

	function ctcp( $src , $dest , $ctcp , $message = NULL ) {
		if( $message != NULL ) {
			$this->msg( $src, $dest, "\001" . strtoupper( $ctcp ) . ' ' . $message . "\001" );
		} else {
			$this->msg( $src, $dest, "\001" . strtoupper( $ctcp ) . "\001" );
		}
	}

	function ctcpreply( $src , $dest , $ctcp , $reply = NULL ) {
		if( $reply != NULL ) {
			$this->notice( $src, $dest, "\001" . strtoupper( $ctcp ) . ' ' . $reply . "\001" );
		} else {
			$this->notice( $src, $dest, "\001" . strtoupper( $ctcp ) . "\001" );
		}
	}

	function addnick( $server , $nick , $ident , $host , $name ) {
		if ( User::newFromNick( $nick ) != null) {
			$this->raw('SVSKILL ' . $nick . ' :Nick collision by services');
		}
		$this->raw( 'NICK ' . $nick . ' 1 '. time() . ' ' . $ident . ' ' . $host . ' ' . $server . ' 0 :' . $name );
	}

	function join( $nick , $chan ) {
		$this->raw( ':' . $nick . ' JOIN ' . $chan );
	}

	function part( $nick , $chan , $reason = NULL ) {
		if( $reason != NULL ) {
			$this->raw( ':' . $nick . ' PART ' . $chan . ' :' . $reason );
		} else {
			$this->raw( ':' . $nick . ' PART ' . $chan );
		}
	}

	function mode( $nick , $chan , $mode ) {
		$this->raw( ':' . $nick . ' MODE ' . $chan . ' ' . $mode );
		$this->event_raw_in( ':' . $nick . ' MODE ' . $chan . ' ' . $mode );
	}

	function kick( $nick , $chan , $who , $reason ) {
		$this->raw( ':' . $nick . ' KICK ' . $chan . ' ' . $who . ' :' . $reason );
		event( 'kick', $nick, $who, $chan, $reason );
	}

	function invite( $nick , $chan , $who ) {
		$this->raw( ':' . $nick . ' INVITE ' . $who . ' ' . $chan );
	}

	function topic( $nick , $chan , $topic ) {
		$this->raw( ':' . $nick . ' TOPIC ' . $chan . ' :' . $topic );
	}

	function svsmode( $nick , $who , $mode ) {
		$this->raw( ':' . $nick . ' SVSMODE ' . $who . ' ' . $mode );
	}

	function chghost( $from , $nick , $host ) {
		// $from = the nick to source the change from (ie HostServ)
		// $nick = the nick recieving the change
		// $host = the new hostname
		$this->raw( ':' . $from . ' CHGHOST ' . $nick . ' ' . $host );
	}

	function remhost( $from , $nick ) {
		// $from = Who to propegate the change from (if applicable)
		// $nick = Who to remove a host from
		$this->svsmode( $from, $nick, '-xt+x' );
	}

	function eos( $server = NULL ) {
		if( $server != NULL ) {
			$this->raw( ':' . $server . ' EOS' );
		} else {
			$this->raw( 'EOS' );
		}
	}

	function squit( $server , $reason ) {
		$this->raw( 'SQUIT ' . $server . ' :' . $reason );
	}

	function quit( $nick , $reason ) {
		$this->raw( ':' . $nick . ' QUIT :' . $reason );
		event( 'quit', $nick, $reason );
	}

	function msg( $src , $dest , $message ) {
		$this->raw( ':' . $src . ' PRIVMSG ' . $dest . ' :' . $message );
	}

	function servmsg( $dest , $message ) {
		$this->raw( 'PRIVMSG ' . $dest . ' :' . $message );
	}

	function notice( $src , $dest , $message ) {
		$this->raw( ':' . $src . ' NOTICE ' . $dest . ' :' . $message );
	}

	function servnotice( $dest , $message ) {
		$this->raw( 'NOTICE ' . $dest . ' :' . $message );
	}

	function svsnick( $old , $new ) {
		$this->raw( ':' . Database::getsetting( 'server' ) . ' SVSNICK ' . $old . ' ' . $new . ' ' . time() );
		// Don't emit an event here because the nick message gets echoed back to PHPserv
	}

	function kill( $nick , $reason ) {
		$this->raw( 'KILL ' . $nick . ' :' . $reason );
		event( 'kill', Database::getsetting( 'server' ), $nick, $reason );
	}

	function shun( $from , $to , $time , $reason ) {
		$this->nicetkl( 's', $to, $time, $reason, $from );
	}

	function gline( $from , $to , $time , $reason ) {
		$this->nicetkl( 'G', $to, $time, $reason, $from );
	}

	function gzline( $from , $to , $time , $reason ) {
		$this->nicetkl( 'Z', $to, $time, $reason, $from );
	}

	function sajoin( $from , $who , $to ) {
		$this->raw( ':' . $from . ' SAJOIN ' . $who . ' ' . $to );
		event( 'join', $who, $to );
	}

	function svskill( $nick , $reason ) {
		$this->raw( 'SVSKILL ' . $nick . ' :' . $reason );
		event( 'svskill', Database::getsetting( 'server' ), $nick, $reason );
	}

	function swhois( $nick , $swhois = '' ) {
		$this->raw( 'SWHOIS ' . $nick . ' :' . $swhois );
	}

	function nicetkl( $type , $mask , $duration , $reason , $source = null ) {
		
		if( $mask{ 0 } == '+' ) {
			$mode = '+';
			$mask = substr( $mask, 1 );
		} else 
			if( $mask{ 0 } == '-' ) {
				$mode = '-';
				$mask = substr( $mask, 1 );
			} else {
				$mode = '+';
			}
		
		if( strpos( $mask, '!' ) !== false ) {
			logit( '[ircd] [tkl] [error] Cannot have "!" in masks.' );
			return 0;
		}
		if( $mask{ 0 } == ':' ) {
			logit( '[ircd] [tkl] [error] Mask cannot start with a ":".' );
			return 0;
		}
		if( strpos( $mask, ' ' ) !== false ) {
			logit( '[ircd] [tkl] [error] FAIL! FAIL! FAIL!  Masks can not have spaces in them ...' );
			return 0;
		}
		
		if( strpos( $mask, '@' ) !== false ) {
			if( ( $mask{ 0 } == '@' ) or ( substr( $mask, -1 ) == '@' ) ) {
				logit( '[ircd] [tkl] [error] No user@host specified.' );
				return 0;
			}
			
			$usermask = explode( '@', $mask, 2 );
			$hostmask = $usermask[ 1 ];
			$usermask = $usermask[ 0 ];
			
			if( $hostmask{ 0 } == ':' ) {
				logit( '[ircd] [tkl] [error] For (weird) technical reasons you cannot start the host with a ":", sorry.' );
				return 0;
			}
			
			if( ( ( $type == 'z' ) or ( $type == 'Z' ) ) and ( $mode == '+' ) ) {
				if( $usermask != '*' ) {
					logit( '[ircd] [tkl] [error] (g)zlines must be placed at *@ipmask, not user@ipmask. This is because (g)zlines are processed BEFORE dns and ident lookups are done. If you want to use usermasks, use a KLINE/GLINE instead.' );
					return -1;
				}
				if( preg_match( '/[A-Za-z]/', $hostmask ) ) {
					logit( '[ircd] [tkl] [error] (g)zlines must be placed at *@IPMASK, not *@HOSTMASK (so for example *@192.168.* is ok, but *@*.aol.com is not). This is because (g)zlines are processed BEFORE dns and ident lookups are done. If you want to use hostmasks instead of ipmasks, use a KLINE/GLINE instead.' );
					return -1;
				}
			}
		} else {
			$nickdata = Database::get( Database::sql( 'SELECT * FROM `users` WHERE `nick` = ' . Database::escape( $mask ) ) );
			if( is_array( $nickdata ) ) {
				$usermask = '*';
				if( ( $type == 'z' ) or ( $type == 'Z' ) ) {
					$hostmask = $nickdata[ 'ip' ];
					if( !$hostmask ) {
						logit( '[ircd] [tkl] [error] Could not get IP address for user "' . $mask . '".' );
						return 0;
					}
				} else {
					$hostmask = $nickdata[ 'host' ];
					if( !$hostmask ) {
						logit( '[ircd] [tkl] [error] Could not get host address for user "' . $mask . '".' );
						return 0;
					}
				}
			} else {
				logit( '[ircd] [tkl] [error] No such nick "' . $mask . '".' );
				return 0;
			}
		}
		
		$secs = 0;
		
		if( $mode == '+' ) {
			$secs = $this->atime( $duration );
			if( $secs < 0 ) {
				logit( '[ircd] [tkl] [error] The time you specified is out of range!' );
				return 0;
			}
		}
		
		if( $secs != 0 ) {
			$secs += time();
		}
		
		$this->tkl( Database::getsetting( 'server' ), $mode, $type, $usermask, $hostmask, ( $source == null ) ? 'PHPServ!PHPServ@phpserv.cluenet.org' : $source, $secs, time(), $reason );
	}

	function tkl( $server , $mode , $type , $ident , $host , $source , $expiry , $set , $reason ) {
		$this->raw( ':' . $server . ' TKL ' . $mode . ' ' . $type . ' ' . $ident . ' ' . $host . ' ' . $source . ' ' . $expiry . ' ' . $set . ' :' . $reason );
	}

	function svso( $nick , $mode ) {
		$this->raw( 'SVSO ' . $nick . ' ' . $mode );
	}

	function isValidNick( $nick ) {
		return preg_match( '#^[a-zA-Z\\\\[\]{}][a-zA-Z0-9\x2d\x5b-\x5e\x60\x7b\7d]*$#', $nick );
	}

	function isValidHost( $host ) {
		$validity = preg_match( '/[^-a-z\d.]/i', $host );
		// Since the function is "is it a valid host", reverse the return value from preg_match()
		switch( $validity ) {
			case 1:
				return 0;
			case 0:
				return 1;
		}
	}

	function event_logout( $from ) {
		$me = Database::getsetting( 'server' );
		$this->svsmode( $me, $from, '+d 0' );
		$this->svsmode( $me, $from, '-r' );
	}

	function event_identify( $from , $uid ) {
		$me = Database::getsetting( 'server' );
		$this->svsmode( $me, $from, '+d ' . $uid );
		$this->svsmode( $me, $from, '+r' );
	}

}

function registerm() {
	$class = new unreal();
	register( $class, __FILE__, 'UnrealIRCd Server Module', 'ircd' );
}
?>
