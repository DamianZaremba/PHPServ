<?php
/*
 * IRCd.php created on Oct 18, 2010 by Josh "SnoFox" Johnson
 *
 */
/*
 * Commands that aren't in here but might work:
 * - globops
 * 		--SnoFox
 */
abstract class IRCd {
	protected abstract function raw ( $string );
	/* Standard user functions */
	public abstract function join( PHPServBot $from, Channel $channel );
	public abstract function part( PHPServBot $from, Channel $channel, $message = '' );
	public abstract function kick( PHPServBot $from, Channel $channel, User $user, $reason );
	public abstract function mode( PHPServBot $from, Channel $channel, Mode $mode );
	public abstract function invite( PHPServBot $from, User $user, Channel $channel );
	public abstract function topic( PHPServBot $from, Channel $channel, $newTopic );
	public abstract function nick( PHPServBot $from, $to, $timestamp = time() );
	public abstract function quit( PHPServBot $from, $message );
	// Messages
	public abstract function privmsg( PHPServBot $from, User $user, $message );
	public abstract function chanmsg( PHPServBot $from, Channel $channel, $message, $prefix = null );
	public abstract function ctcp( PHPServBot $from, User $user, $type, $message );
	public abstract function ctcpreply( PHPServBot $from, User $user, $type, $message );
	public abstract function notice( PHPServBot $from, User $user, $message );
	public abstract function chanNotice( PHPServBot $from, Channel $channel, $message, $prefix = null );
	/* Oper functions */
	public abstract function servmsg( PHPServBot $from, $serverMask, $message );
	public abstract function wallops( PHPServBot $from, $message );
	public abstract function kill( PHPServBot $from, User $user, $message );
	public abstract function kline( PHPServBot $from, $banMask, $expiry, $reason );
	public abstract function squit( PHPServBot $from, $servMask );
	/* Server functions */
	public abstract function addNick( Module $module, $nick, $identd, $address, $gecos );
	public abstract function addServ( Module $module, $servName, $servDesc );
	
	/* Other random odds and ends */
	public abstract function isValidNick( $nick );
	public abstract function isValidHost( $hostname );
	 
}
?>
