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
	public abstract function kick( PHPServBot $from, Channel $channel, User $user, $reason );
	public abstract function mode( PHPServBot $from, Channel $channel, Mode $mode );
	public abstract function privmsg( PHPServBot $from, User $user, $message);
	// "Prefix" as in /msg @#clueirc --SnoFox
	public abstract function chanmsg( PHPServBot $from, Channel $channel, $message, $prefix = null );
	// /msg $*.cluenet.org Hi world! --SnoFox
	public abstract function servmsg( PHPServBot $from, $serverMask, $message);
	public abstract function ctcp( PHPServBot $from, User $user, $type, $message );
	public abstract function ctcpreply( PHPServBot $from, User $user, $type, $message);
	public abstract function notice( PHPServBot $from, User $user, $message);
	public abstract function chanNotice( PHPServBot $from, Channel $channel, $message, $prefix = null );
	public abstract function kill( PHPServBot $from, User $user, $message);
	public abstract function join( PHPServBot $from, Channel $channel);
	public abstract function part( PHPServBot $from, Channel $channel, $message = '');
	public abstract function invite( PHPServBot $from, User $user, Channel $channel);
	public abstract function topic( PHPServBot $from, Channel $channel, $newTopic);
	public abstract function wallops( PHPServBot $from, $message);
	public abstract function squit( PHPServBot $from, $servMask);
	public abstract function kline( PHPServBot $from, $banMask, $expiry, $reason);
	// Is this legal? Or necessary? (the timestamp) :P --SnoFox
	public abstract function nick( PHPServBot $from, $to, $timestamp = time());
	public abstract function quit( PHPServBot $from, $message);
	protected abstract function raw ( $string );
}
?>
