<?PHP

	class foxbot {
	
	private $set;
	private	$config;
	
	function construct() {
		$this->config = array (
			'nick' => 'Unix',
			'user' => 'Unix',
			'host' => 'SnoFox.net',
			'gecos' => 'SnoFox\'s friend',
			'chan' => array (
				'main' => '#FoxDen',
				'secure' => '#FoxSecure',
			)
		);
		
		$this->set = unserialize(file_get_contents('foxbot.db'));
		
		$this->doBotStart('load');
	}
	
	function destruct() {
		
	}
	
	function doBotStart($type = 'start') {
	// $type definitions:
	// load = called due to module load
	// start = (re)start the bot. (Ex, was killed)
		$ircd = &ircd();
		global $mysql;
		$config = $this->config;
	
		switch ($type) {
		case 'start':
			// Try to prevent flood-respawns
			sleep(2);
			break;
		case 'load':
			// Doesn't do anything atm...
			break;
		case 'default':
			// Shouldn't hit, but let's be safe
			break;
		}
		
		$ircd->addnick($mysql->getsetting('server'),$config['nick'],$config['user'],$config['host'],$config['gecos']);
		$ircd->mode($config['nick'],$config['nick'],'+oSpB');
		$ircd->join($config['nick'],$config['chan']['main']);
		$ircd->mode($config['nick'],$config['chan']['main'],'+h '.$config['nick']);
		$ircd->join($config['nick'],$config['chan']['secure']);
//		$ircd->svsmode($config['nick'],$config['chan']['secure'],'-vhoaqIeb');
		$ircd->mode($config['nick'],$config['chan']['secure'],'+siIao *!*@SnoFox.net '.str_repeat($config['nick'].' ',2));
		
	}
	
	function saveset() {
		file_put_contents('foxbot.db',serialize($this->set));
	}
	
	function chkAccess($nick,$level) {
		$nickd = $mysql->get($mysql->sql('SELECT * FROM `users` WHERE `nick` = '.$mysql->escape($from)));
		$uid = $nickd['loggedin'];
		$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
		if (($user['level'] > $level) || ($user['user'] == 'SnoFox')) {
			return 1;
		} else {
			return 0;
		}
	}
	
	function doJoin($channel) {
		$ircd = &ircd();
		$this->set['chan'][strtolower($channel)] = 1;
		$ircd->msg($this->config['nick'],$this->config['chan']['secure'],'\003IRC\003: '.$this->config['nick'].' has joined '.$channel);
		$ircd->join($this->config['nick'],$channel);
		$this->saveset();
	}

	function doPart($chan,$reason) {
		$ircd = &ircd();
		$ircd->msg($this->config['nick'],$this->config['chan']['secure'],'\003IRC\003: '.$this->config['nick'].' has left '.$chan);
		$ircd->part($this->config['nick'],$chan,$reason);
		unset($this->set['chan'][strtolower($chan)]);
		$this->saveset();
	}
	
	function event_msg ($from,$to,$message) {
		if ($to[0] == '#') {
			if ($this->set['chan'][strtolower($to)] == 1) {
				return $this->thisIsAChanMsg($from,$to,$message);
			}
		}
		if (strtolower($to) == strtolower($this->config['nick'])) {
			$ircd = &ircd();
			$config = $this->config;
			$to = explode('@', $to, 2);
			$to = $to[0];
			$d = explode(' ', $message);
		
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003Message\003: <'.$from.'/PM> '.$message);
		} 
	}
	
	function thisIsAChanMsg($nick,$chan,$message) {
		$message = explode(' ',$message,2);
		$cmd = $message[0];
		$message = $message[1];
		$config = $this->config;
		$ircd = &ircd();
		
		switch ($cmd) {
			case '!fjoin':
				if ($this->chkAccess($nick,'599')) {
					if ($message[0] != '') {
						$this->doJoin($message);
					} else {
						$ircd->notice($config['nick'],$nick,'Join where?');
					}
				} else { 
					$ircd->notice($config['nick'],$nick,'Insufficient access.');
				}
				break;
			case '!part':
				$this->doPart($chan,'Requested by '.$nick);
				break;
			case '!fpart':
				$message = explode(' ',$message,2);
				$where = $message[0];
				if (isset($message[1]))
					$why = $message[1];
				if ($this->chkAccess($nick,'599'))
					$this->doPart($chan,(isset($why) ? $why : 'Requested by '.$nick));
				break;
			case 'default':
				return;
		}
	}
	
	function event_logger($string) {
		$string = explode(' ',$string,1);
		$src = $string[0];
		$msg = $string[1];
		if ($src == '[HostServ]') {
			$config = $this->config;
			$ircd = &ircd();
			
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003HostServ\003: '.$msg);
		}
	}
	
	function event_identify($from,$uid) {
		$ircd = &ircd();
		global $mysql;
		$config = $this->config;
		
		$user = $mysql->get($mysql->sql('SELECT * FROM `access` WHERE `id` = '.$mysql->escape($uid)));
		
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Identify\003: '.$from.'['.$user['user'].'\015; '.$uid.'] identified to PHPserv');
	}
	
	function event_logout($from,$user) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Logout\003: '.$from.' logged out of account '.$user['user'].'(UID '.$user['uid'].')');
	}
	
	function event_signon($nick,$user,$host,$real,$ip,$server,$stamp=0) {
		// scrawl57!~scrawl@data.searchirc.org * SearchIRC Crawler
		// tunix29!~atte@echo940.server4you.de * netsplit.de
		if (!preg_match('/^(scrawl|tunix)\d+!~?(scrawl|atte)@(data|echo\d+)\.(searchirc\.org|server4you\.de)/i',$nick.'!'.$user.'@'.$host)) {
			$ircd = &ircd();
			$config = $this->config;

			$ircd->msg($config['nick'],$config['chan']['secure'],'\003Connect\003: Client connecting on '.$server.': '.$nick.'!'.$user.'@'.$host.' ['.$ip.'] ('.$real.')');
		}
	}
	
	function event_quit($nick,$reason) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Disconnect\003: '.$nick.' disconnected from the network.');
	}

	function event_nick($old,$new) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Nick\003: '.$old.' changed their nickname to '.$new);
	}
	
	function event_ctcp ($from,$to,$type,$msg) {
		$config = $this->config;
		if (strtolower($to) == $config['nick'] && strtoupper($type) != 'ACTION') {
			$ircd = &ircd();
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003CTCP\003: Got CTCP from '.$from.': '.$type.' '.$msg);
		}
	}
	
	function event_ctcpreply ($from,$to,$ctcp,$message = NULL) {
		$config = $this->config;
		if (strtolower($to) == $config['nick']) {
			$ircd = &ircd();
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003CTCP\003: Got CTCP reply from '.$from.': '.$ctcp.' '.$message);
		}
	}
		
	function event_notice ($from,$to,$message) {
		$config = $this->config;
		if (strtolower($to) == $config['nick']) {			
			$ircd = &ircd();
		
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003Message\003: <'.$from.'/Notice> '.$message);
		}
	}
		
	function event_kick ($src,$pwntUser,$chan,$reason) {
		if (strtolower($nick) == strtolower($this->config['nick'])) {
			$ircd = &ircd();
			$ircd->msg($config['nick'],$config['chan']['secure'],'\003IRC\003: '.$src.' kicked '.$config['nick'].' from '.$chan.'\015 ('.$reason.'\015)');
			$ircd->msg($config['nick'],$chan,'All you had to do was ask! :(');
			unset($this->set['chan'][strtolower($chan)]);
			$this->saveset();
		}
	}
		
	function event_join ($nick,$channel) {
		$config = $this->config;
		
		if ($channel == $config['chan']['secure']) {
			if ($this->chkAccess($nick,599) === 0) {
				$ircd = &ircd();
				$ircd->mode($config['nick'],$config['chan']['secure'],'+bb '.$nick.' '.$nickd['host']);
				$ircd->kick($config['nick'],$config['chan']['secure'],$nick,'You are not authorized to join '.$config['chan']['secure'].'. Required access: >599. Your access: '.$level.'. Ciao!');
			}
		}
	}
	
	function event_channel_create ($channel,$nick) {
		$ircd = &ircd();
		$config = $this->config;
		
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Chan Create\003: '.$channel.'\015 created by '.$nick);
	}
		
	
	function event_channel_destroyed ($channel,$nick,$why) {
		$ircd = &ircd();
		$config = $this->config;
		
		switch ($why) {
			case 'sapart':
			case 'part':
				$msg = 'due to '.$nick.' parting.';
				break;
			case 'svskill':
			case 'kill':
				$msg = 'due to '.$nick.' being killed.';
				break;
			case 'kick':
				$msg = 'due to '.$nick.' being kicked.';
				break;
			case 'quit':
				$msg = 'due to '.$nick.' quitting.';
				break;
			case 'default':
				$msg = 'for unknown reasons.';
			}
		$ircd->msg($config['nick'],$config['chan']['secure'],'\003Chan Destroy\003: '.$channel.'\015 destroyed '.$msg);
		
		if (isset($this->set['chan'][strtolower($channel)])) {
			$ircd->part($config['nick'],$channel);
			unset($this->set['chan'][strtolower($channel)]);
		} else {
			return;
		}
		$this->saveset();
	}
	
	function event_invite($nick,$to,$chan) {
		$config = $this->config;
		if (strtolower($to) != strtolower($config['nick'])) {
			return;
		}
		
		$ircd = ircd();
		
		$this->doJoin($config['nick'],$chan);
		$ircd->msg($config['nick'],$chan,'Hey '.$chan.', sup? My name is '.$config['nick'].', and I\'m a robot! '.$nick.' invited me to join, so here I am! If you want me to leave, type !part. Ciao!');
	}
	
	function registerm () {
		$class = new foxbot;
		register($class, __FILE__, 'FoxBot Module', 'foxbot');
	}
}
?>