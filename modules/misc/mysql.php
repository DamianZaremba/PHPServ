<?PHP
	class mysqlserv {

		function check_empty ($nick = '<unknown>', $why = 'unknown') {
			// nick = the nickname of the user who destroyed the channel
			// why = the exact reason it was destroyed
			// Normal reasons: (sa)part, quit, (svs)kill, kick, unknown
			global $mysql;
			$res = $mysql->sql('SELECT `chanid`,`name` FROM `channels` WHERE `chanid` NOT IN (SELECT `channels`.`chanid` FROM `channels`, `user_chan` WHERE `channels`.`chanid` = `user_chan`.`chanid` GROUP BY `channels`.`chanid` HAVING COUNT(*) > 0)');

			while ($x = $mysql->get($res)) {
				$mysql->sql('DELETE FROM `channels` WHERE `chanid` = \''.$mysql->escape($x['chanid']).'\'');
				event('channel_destroyed',$x['name'],$nick, $why);
			}
		}

		function event_signon ($nick,$user,$host,$real,$ip,$server,$stamp=0) {
			global $mysql;
			$servid = $mysql->get($mysql->sql('SELECT `servid` FROM `servers` WHERE `name` = '.$mysql->escape($server)));
			$servid = $servid['servid'];
			$data = Array (
				'userid'	=> 'NULL',
				'nick'		=> $nick,
				'user'		=> $user,
				'host'		=> $host,
				'realname'	=> $real,
				'ip'		=> $ip,
				'servid'	=> $servid,
				'loggedin'	=> $stamp == 0 ? -1 : $stamp
			);
			$mysql->insert('users', $data);	
		}

		function event_quit ($nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'quit');
		}

		function event_kill ($from,$nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'kill');
		}

		function event_svskill ($from,$nick,$message) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `userid` = \''.$userid.'\'');
			$mysql->sql('DELETE FROM `users` WHERE `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'svskill');
		}

		function event_join ($nick,$channel) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			if (!$chanid) {
				$data = Array (
					'chanid'	=> 'NULL',
					'name'		=> $channel
				);
				$mysql->insert('channels',$data);
				$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
				$chanid = $chanid['chanid'];
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid,
					'modes'		=> 'o'
				);
				event('channel_create',$channel,$nick);
			} else {
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid
				);
			}
			$mysql->insert('user_chan',$data);
		}

		function event_sajoin ($from,$nick,$channel) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			if (!$chanid) {
				$data = Array (
					'chanid'	=> 'NULL',
					'name'		=> $channel
				);
				$mysql->insert('channels',$data);
				$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
				$chanid = $chanid['chanid'];
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid,
					'modes'		=> 'o'
				);
			} else {
				$data = Array (
					'id'		=> 'NULL',
					'userid'	=> $userid,
					'chanid'	=> $chanid
				);
			}
			$mysql->insert('user_chan',$data);
		}

		function event_part ($nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'part');
		}

		function event_sapart ($from,$nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'sapart');
		}

		function event_kick ($from,$nick,$channel,$reason) {
			global $mysql;
			$userid = $mysql->get($mysql->sql('SELECT `userid` FROM `users` WHERE `nick` = '.$mysql->escape($nick)));
			$userid = $userid['userid'];
			$chanid = $mysql->get($mysql->sql('SELECT `chanid` FROM `channels` WHERE `name` = '.$mysql->escape($channel)));
			$chanid = $chanid['chanid'];
			$mysql->sql('DELETE FROM `user_chan` WHERE `chanid` = \''.$chanid.'\' AND `userid` = \''.$userid.'\'');
			$this->check_empty($nick,'kick');
		}

/*		function event_mode ($from,$to,$mode) {
			global $mysql;
			if ('#' == mid($to,0,1)) {
				//channel mode
				
				
			} else {
				//user mode
				
			}
		}*/

		function event_nick ($from,$to) {
			global $mysql;
			$mysql->sql('UPDATE `users` SET `nick` = '.$mysql->escape($to).' WHERE `nick` = '.$mysql->escape($from));
		}
	}

	function registerm () {
		$class = new mysqlserv;
		register($class, __FILE__, 'MySQL Module', 'MySQL');
	}
?>
