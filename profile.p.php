
<?php
date_default_timezone_set('Europe/Bucharest');
$time = time();
?>  

<?php 
$w = Config::$g_con->prepare('SELECT * FROM `accounts` WHERE `username` = ?');
$w->execute([Config::$_url[1]]);
if (!$w->rowCount()) {
    Config::gotoPage("");
} else {
    $profile = $w->fetch(PDO::FETCH_OBJ);

    // Haal gegevens op uit de characters-tabel
    $c = Config::$g_con->prepare('SELECT * FROM `characters` WHERE `account` = ?');
    $c->execute([$profile->id]); // Gebruik de `id` van de account als parameter

    if ($c->rowCount()) {
        $character = $c->fetch(PDO::FETCH_OBJ); // Meerdere characters ophalen
    } else {
        $character = null; // Geen characters gevonden
    }
}




if(Config::isAdmin(Config::getUser())) {
	if(isset($_POST['unban'])) {
		$w = Config::$g_con->prepare('DELETE FROM `bans` WHERE `account` = ?');
		$w->execute(array($profile->id));
		$notif = 'You\'ve been unbanned by '.Config::getData("accounts","username",Config::getUser()).'!';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		Config::gotoPage("profile/".$profile->username."",0,"success","Player has been unbanned with success!");
	}
	
	if(isset($_POST['unsuspend'])) {
		$w = Config::$g_con->prepare('DELETE FROM `wcode_suspend` WHERE `User` = ?');
		$w->execute(array($profile->username));
		$notif = 'You\'ve been unsuspended by '.Config::getData("accounts","username",Config::getUser()).'!';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		Config::gotoPage("profile/".$profile->username."",0,"success","Player has been unsuspended with success!");
	}
	
	if(isset($_POST['s_permanent']) && strlen($_POST['s_res'])) {
		if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
			Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');
			$notif = 'Admin '.Config::getName(Config::getUser(),false).' attempted to suspend you.';
			$link = Config::$_PAGE_URL.'profile/' . Config::getName(Config::getUser(),false);
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			return;
		}
		$w = Config::$g_con->prepare('INSERT INTO `wcode_suspend` (`User`,`Userid`,`Admin`,`Adminid`,`Date_unix`,`Reason`) VALUES (?,?,?,?,?,?)');
		$w->execute(array($profile->username,$profile->id,Config::getData("accounts","username",Config::getUser()),Config::getUser(),time(),$_POST['s_res']));
		
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' suspended permanently player '.$profile->username.' for '.$_POST['s_res'].'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You\'ve been permanently suspended from panel!';
		$link = Config::$_PAGE_URL.'';
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		Config::gotoPage('profile/'.$profile->username.'',0,'success','Player has been suspended from panel permanently!');
	}
	if(isset($_POST['s_temp']) && strlen($_POST['s_res']) && strlen($_POST['s_days']) && is_numeric($_POST['s_days'])) {
		if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
			Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');
			$notif = 'Admin '.Config::getName(Config::getUser(),false).' attempted to suspend temporarly you.';
			$link = Config::$_PAGE_URL.'profile/' . Config::getName(Config::getUser(),false);
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			return;
		}
		$expire = time() + (86400 * $_POST['s_days']);
		$w = Config::$g_con->prepare('INSERT INTO `wcode_suspend` (`User`,`Userid`,`Admin`,`Adminid`,`Date_unix`,`Reason`,`Days`,`Expire_unix`,`Expire`) VALUES (?,?,?,?,?,?,?,?,?)');
		$w->execute(array($profile->username,$profile->id,Config::getData("accounts","username",Config::getUser()),Config::getUser(),time(),$_POST['s_res'],$_POST['s_days'],$expire,gmdate("Y-m-d H:i:s", $expire)));
		
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' suspended temporary for '.$_POST['days'].' days, player '.$profile->username.' for '.$_POST['s_res'].'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You\'ve been suspended temporary!';
		$link = Config::$_PAGE_URL.'unban';
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		Config::gotoPage('profile/'.$profile->username.'',0,'success','Player has been suspended temporary for '.$_POST['s_days'].' days!');
	}
	
	
	if(isset($_POST['submit_action'])) {
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' reseted '.$_POST['submit_action'].' field of player '.$profile->username.'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You have no more '.$_POST['submit_action'].' points thanks to '.Config::getName(Config::getUser(),false).'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `'.$_POST['submit_action'].'` = 0 WHERE `id` = ?');
		$w->execute(array($profile->id));
		$var = intval($_POST['submit_action']);
		$profile->$var = 0;
		echo Config::csSN("success","Pharameter(".$_POST['submit_action'].") has been reseted with succes!");
	}
	
	if(isset($_POST['warnup'])) {
		if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
			Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');
			$notif = 'Admin '.Config::getName(Config::getUser(),false).' attempted to warn you up.';
			$link = Config::$_PAGE_URL.'profile/' . Config::getName(Config::getUser(),false);
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			return;
		}
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' gave one Warning Point to player '.$profile->username.' for: '.$_POST['reason'].'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You received +1 WarnPoint from '.Config::getName(Config::getUser(),false).' for '.$_POST['reason'].'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `punishpoints` = `punishpoints`+1 WHERE `id` = ?');
		$w->execute(array($profile->id));
		if($profile->punishpoints == 2) {
			$notif = 'After acumulating 3/3 points you\'ve been banned for 7 days.';
			$link = Config::$_PAGE_URL.'profile/' . $profile->username;
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			
			$w = Config::$g_con->prepare('INSERT INTO `bans` (`PlayerName`,`AdminName`,`Reason`,`IP`) VALUES (?,?,?,?)');
			$w->execute(array($profile->username,"AdmBot","3/3 warns",$profile->IP));
			
			$w = Config::$g_con->prepare('UPDATE `accounts` SET `punishpoints` = 0 WHERE `id` = ?');
			$w->execute(array($profile->id));
			
			Config::gotoPage("profile/".$profile->username."",0,"success","This player has been banned for acumulating 3/3 warn points. Last reason being: ".$_POST['reason']."");
		} else Config::gotoPage("profile/".$profile->username."",0,"success","Player got a new warn point for reason: ".$_POST['reason'].".");
	}
	if(isset($_POST['unmute'])) {
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `Muted` = 0, `MuteTime` = 0 WHERE `id` = ?');
		$w->execute(array($profile->id));
		$notif = 'You\'ve been unmuted by '.Config::getData("accounts","username",Config::getUser()).'!';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		Config::gotoPage("profile/".$profile->username."",0,"success","Player has been unmuted with success!");
	}
	if(isset($_POST['mute_action']) && strlen($_POST['mute_res'])) {	
		if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
			Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');
			$notif = 'Admin '.Config::getName(Config::getUser(),false).' attempted to mute you.';
			$link = Config::$_PAGE_URL.'profile/' . Config::getName(Config::getUser(),false);
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			return;
		}	
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' muted('.$_POST['mute_min'].' minutes) player '.$profile->username.' for '.$_POST['mute_res'].'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You\'ve been muted for '.$_POST['mute_min'].' minutes!';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `Muted` = 1, `MuteTime` = ? WHERE `id` = ?');
		$w->execute(array($_POST['mute_min']*60,$profile->id));
		Config::gotoPage("profile/".$profile->username."",0,"success","Player have been muted(".$_POST['mute_min']." minutes) for reason: ".$_POST['mute_res'].".");
	}
	if(isset($_POST['warndown'])) {
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' took one Warning Point to player '.$profile->username.' for: '.$_POST['reason'].'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		
		$notif = 'You have one WarnPoint less from '.Config::getName(Config::getUser(),false).' for '.$_POST['reason'].'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `punishpoints` = `punishpoints`-1 WHERE `id` = ?');
		$w->execute(array($profile->id));
		Config::gotoPage("profile/".$profile->username."",0,"success","Player have less one warn point for reason: ".$_POST['reason'].".");
	}
	
	if(isset($_POST['delete_ac'])) {
		$w = Config::$g_con->prepare('DELETE FROM `faction_logs` WHERE `id` = ?');
		$w->execute(array($_POST['delete_ac']));
		$log = 'Admin '.Config::getData("accounts","username",Config::getUser()).' deleted log ID #'.$_POST['delete_ac'].' from faction history of '.$profile->username.'.';
		Config::insertLog(Config::getUser(),Config::getData("accounts","username",Config::getUser()),$log,$profile->id,$profile->username);
		echo Config::csSN("success","Faction history line #".$_POST['delete_ac']." has been deleted!");
	}
	if(isset($_POST['mon_submit']) && Config::isAdmin(Config::getUser(), 5))
	{
		$notif = 'Your money state('.Config::formatNumber($_POST['money_n']).' / '.Config::formatNumber($_POST['bank_n']).') has been updated by Admin '.Config::getName(Config::getUser(),false).'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		$w = Config::$g_con->prepare('UPDATE `characters` SET `money` = ?, `bankmoney` = ? WHERE `account` = ?');
		$w->execute(array($_POST['money_n'],$_POST['bank_n'],$profile->id));
		$character->money = $_POST['money_n'];
		$character->bankmoney = $_POST['bank_n'];
		echo Config::csSN("success","Money got updated with succes!");
		
	}
	if(isset($_POST['ppr_submit']) && Config::isAdmin(Config::getUser(), 5))
	{
		$notif = 'Your premium points('.$_POST['pointsp'].') has been updated by Admin '.Config::getName(Config::getUser(),false).'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `credits` = ? WHERE `id` = ?');
		$w->execute(array($_POST['pointsp'],$profile->id));
		$profile->credits = $_POST['pointsp'];
		echo Config::csSN("success","Premium Points has been updated with success!");

	}
}
if(Config::isLogged(Config::getUser()) && (Config::isAdmin(Config::getUser()) || $profile->id == Config::getUser())) 
	if(isset($_POST['avatar_submit']))
	{
	{
		$notif = 'Your avatar has been updated by Admin '.Config::getName(Config::getUser(),false).'.';
		$link = Config::$_PAGE_URL.'profile/' . $profile->username;
		Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
		$w = Config::$g_con->prepare('UPDATE `accounts` SET `avatar` = ? WHERE `id` = ?');
		$w->execute(array($_POST['avatar'],$profile->id));
		$profile->credits = $_POST['avatar'];
		echo Config::csSN("success","Your Avatar has been updated with success!");

	}
}
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);
if(Config::isLogged(Config::getUser()) && (Config::isAdmin(Config::getUser()) || $profile->id == Config::getUser())) { 
	if(isset($_POST['email_submit']))
	{
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
		{
			$notif = 'Your email has been changed by '.Config::getName(Config::getUser(),false).'.';
			$link = Config::$_PAGE_URL.'profile/' . $profile->username;
			Config::makeNotification($profile->id,$profile->username,$notif,Config::getUser(),Config::getData("accounts","username",Config::getUser()),$link);
			
			$w = Config::$g_con->prepare('UPDATE `accounts` SET `email` = ? WHERE `id` = ?');
			$w->execute(array($_POST['email'],$profile->id));
			$profile->email = $_POST['email'];
			echo Config::csSN("success","Email has been changed with success!");
		} else echo Config::csSN("danger","Please insert an valid email form!");
	}
}

if(Config::isLogged() && Config::getData("accounts","admin",Config::getUser())) {
?>
<div id="givewarn" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<form method="post"><center>
				<?php
					if($profile->punishpoints < 3) echo '<button type="submit" class="btn btn-success" title="up" name="warnup"><i class="fa fa-plus"></i><span class="sr-only">up</span></button>';
					if($profile->punishpoints > 0) echo ' <button type="submit" class="btn btn-danger" title="down" name="warndown"><i class="fa fa-minus"></i><span class="sr-only">down</span></button>';
				?>
					<br><br><input class="form-control" placeholder="Reason" type="text" name="reason" required>
				</center></form>
			</div>
		</div>
	</div>
</div>
<div id="suspend" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-warning"> </i> Suspend player's access to panel</h5>
				<form method="post">
					<input class="form-control input-sm" placeholder="Reason" type="text" name="s_res" required><br>
					<center>
						<input class="form-control input-sm" placeholder="Days" type="text" style="width: 20%" name="s_days"><br>					
						<button type="submit" name="s_permanent" class="btn btn-success btn-xs" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="No need to complete with days!" data-original-title="">Permanent</button>
						<button type="submit" name="s_temp" class="btn btn-primary btn-xs" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Be sure you completed for how many days!" data-original-title="">Temporary</button>
					</center>
				</form>
			</div>
		</div>
	</div>
</div>
<div id="givemute" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-legal"> </i> Mute this player</h5>
				<form method="post">
					<input class="form-control input-sm" placeholder="Reason" type="text" name="mute_res" required><br>
					<center>
						<input class="form-control input-sm" placeholder="Time in minutes" type="text" name="mute_min" required><br>					
						<button type="submit" name="mute_action" class="btn btn-success btn-xs" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="No need to complete with days!" data-original-title="">Mute</button>
					</center>
				</form>
			</div>
		</div>
	</div>
</div>
<div id="tagadd" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-code"> </i> Add TAG</h5>
				<form method="post">
					<center><input type="color" value="#ff0000" name="color"><bR>
					<i class="fa fa-info-circle"></i><small> Pick background of tag </small> </center><br>
					
					<label class="fancy-radio">
						<input name="icon" value="fa fa-gear" type="radio" checked>
						<span><i></i><o class="fa fa-gear"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-sitemap" type="radio">
						<span><i></i><o class="fa fa-sitemap"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-comments" type="radio">
						<span><i></i><o class="fa fa-comments"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-shield" type="radio">
						<span><i></i><o class="fa fa-shield"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-legal" type="radio">
						<span><i></i><o class="fa fa-legal"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-bug" type="radio">
						<span><i></i><o class="fa fa-bug"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-code" type="radio">
						<span><i></i><o class="fa fa-code"></o></span>
					</label>
					<label class="fancy-radio">
						<input name="icon" value="fa fa-star" type="radio">
						<span><i></i><o class="fa fa-star"></o></span>
					</label>
					
					<p><input type="text" class="form-control" placeholder="TAG Name" name="tag"></p>
					
					<center><button type="submit" name="insert" class="btn btn-default btn-xs">INSERT TAG</button></center>
				</form>
			</div>
		</div>
	</div>
</div>

<?php 
}
if(Config::isLogged(Config::getUser()) && (Config::isAdmin(Config::getUser()) || $profile->id == Config::getUser())) { ?>
<div id="moneym" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<p>Changing of Money</p>
				<form method="post" action="#">
					<div class="input-group">
						<span class="input-group-addon">Money</span>
						<input class="form-control" value="<?php echo $character->money ?>" type="text" name="money_n" required>
					</div><br>
					<div class="input-group">
						<span class="input-group-addon">Bank</span>
						<input class="form-control" value="<?php echo $character->bankmoney ?>" type="text" name="bank_n" required>
					</div>
					<small><i class="fa fa-info-circle"></i> User will receive a notifications after the changes!</small>
					<br><br>
					<button type="submit" name="mon_submit" class="btn btn-warning btn-block"><i class="fa fa-legal"></i> Update</button>
				</form>
			</div>
		</div>
	</div>
</div>
<div id="premiumed" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<p>Changing of Premium Points</p>
				<form method="post" action="#">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-pencil"></i></span>
						<input class="form-control" placeholder="<?php echo $profile->credits ?>" type="text" name="pointsp" required>
					</div>
					<small><i class="fa fa-info-circle"></i> User will receive a notifications after the changes!</small>
					<br><br>
					<button type="submit" name="ppr_submit" class="btn btn-warning btn-block"><i class="fa fa-legal"></i> Update</button>
				</form>
			</div>
		</div>
	</div>
</div>
<div id="changeavatar" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<p>Change your avatar</p>
				<form method="post" action="#">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-pencil"></i></span>
						<input class="form-control" placeholder="<?php echo $profile->avatar ?>" type="text" name="avatar" required>
					</div>
					<small><i class="fa fa-info-circle"></i> Avatar URL! Make sure it ends with .jpg or .png</small>
					<br><br>
					<button type="submit" name="avatar_submit" class="btn btn-primary btn-block"><i class="fa fa-check-circle"></i> Change Avatar</button>
				</form>
			</div>
		</div>
	</div>
</div>
<div id="small-modals" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<p>Insert carefully the email!</p>
				<form method="post" action="#">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-pencil"></i></span>
						<input class="form-control" placeholder="New email" type="text" name="email" required>
					</div>
					<small><i class="fa fa-info-circle"></i> User will receive a notifications after the changes!</small>
					<br><br>
					<button type="submit" name="email_submit" class="btn btn-primary btn-block"><i class="fa fa-check-circle"></i> CHANGE</button>
				</form>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php if(Config::getData("accounts","admin",Config::getUser()) > 0) { ?>
<?php if(isset($_POST['psuspend'])) {
	
if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');	
} else { 	
$reason = htmlspecialchars($_POST['sreason']);
$days = htmlspecialchars($_POST['sdays']);	

if(!$_POST['sreason'] || !$_POST['sdays']) {	
echo '<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> You left fields blank (reason & days).
			</div>';	
} else { 	

if ($days == 999) { 
$suspend = Config::$g_con->prepare('INSERT INTO `wcode_suspend` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Days`,`Reason`,`SuspendDate`,`ExpireDate`,`Permanent`) VALUES (?,?,?,?,?,?,?,?,?)');
$suspend->execute(array($profile->username, $profile->id, Config::getData("accounts","username",Config::getUser()), Config::getData("accounts","id",Config::getUser()), $days, $reason, date('d/m/Y H:i', $time),0,1));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $reason));
} else { 
$suspend = Config::$g_con->prepare('INSERT INTO `wcode_suspend` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Days`,`Reason`,`SuspendDate`,`ExpireDate`,`Permanent`) VALUES (?,?,?,?,?,?,?,?,?)');
$expire = (86400*$days);
$suspend->execute(array($profile->username, $profile->id, Config::getData("accounts","username",Config::getUser()), Config::getData("accounts","id",Config::getUser()), $days, $reason, date('Y-m-d H-i', $time), date('Y-m-d H-i', $time + $expire),0));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('Y-m-d H-i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $reason));
}
?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully suspended.
			</div>
			<meta http-equiv = "refresh" content = "1" />
<?php } } } ?>

<?php if(isset($_POST['punsuspend'])) { 
$k = Config::$g_con->prepare("DELETE FROM `wcode_suspend` WHERE `UserID` = ?"); 
$k->execute(array($profile->id)); ?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully unsuspended.
			</div>
			<meta http-equiv = "refresh" content = "2" />
<?php } ?>

<?php } ?>


<?php if(Config::getData("accounts","admin",Config::getUser()) > 1) { ?>
<?php if(isset($_POST['aban'])) {
	
if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');	
} else { 	
$areason = htmlspecialchars($_POST['areason']);
$atime = htmlspecialchars($_POST['atime']);	

if(!$_POST['areason'] || !$_POST['atime']) {	
echo '<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> You left fields blank (reason & time).
			</div>';	
} else { 	

if ($atime == 999) { 
$quee = Config::$g_con->prepare('INSERT INTO `panel_sanctions` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Type`,`Time`,`Reason`,`Date`) VALUES (?,?,?,?,?,?,?,?)');
$quee->execute(array($profile->username, $profile->id,Config::getData("accounts","username",Config::getUser()),Config::getData("accounts","id",Config::getUser()), 4, $atime, $areason, date('d/m/Y H:i', $time)));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $areason));
} else { 
$que = Config::$g_con->prepare('INSERT INTO `panel_sanctions` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Type`,`Time`,`Reason`,`Date`) VALUES (?,?,?,?,?,?,?,?)');
$que->execute(array($profile->username, $profile->id,Config::getData("accounts","username",Config::getUser()),Config::getData("accounts","id",Config::getUser()), 0, $atime, $areason, date('d/m/Y H:i', $time)));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $areason));
}
?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully sanctioned.
			</div>
			<meta http-equiv = "refresh" content = "1" />
<?php } } } ?>

<?php if(isset($_POST['awarn'])) {
	
if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');	
} else { 	
	
$areason = htmlspecialchars($_POST['areason']);
$atime = htmlspecialchars($_POST['atime']);		
	
if(!$_POST['areason']) {	
echo '<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> You left fields blank (reason).
			</div>';		
} else { 	
if ($profile->Warnings == 2) { 
echo '
<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> This player are 2 warnings, please give ban.
			</div>
'; } else {
$que = Config::$g_con->prepare('INSERT INTO `panel_sanctions` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Type`,`Time`,`Reason`,`Date`) VALUES (?,?,?,?,?,?,?,?)');
$que->execute(array($profile->username, $profile->id,Config::getData("accounts","username",Config::getUser()),Config::getData("accounts","id",Config::getUser()), 1, $atime, $areason, date('d/m/Y H:i', $time)));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $areason));
?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully sanctioned.
			</div>
<meta http-equiv = "refresh" content = "1" />
<?php } } } } ?>


<?php if(isset($_POST['ajail'])) {
	
if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');	
} else { 	
	
$areason = htmlspecialchars($_POST['areason']);
$atime = htmlspecialchars($_POST['atime']);	

if(!$_POST['areason'] || !$_POST['atime']) {	
echo '<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> You left fields blank (reason & time).
			</div>';		
} else { 		
$que = Config::$g_con->prepare('INSERT INTO `panel_sanctions` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Type`,`Time`,`Reason`,`Date`) VALUES (?,?,?,?,?,?,?,?)');
$que->execute(array($profile->username, $profile->id,Config::getData("accounts","username",Config::getUser()),Config::getData("accounts","id",Config::getUser()), 3, $atime, $areason, date('d/m/Y H:i', $time)));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $areason));
?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully sanctioned.
			</div>
<meta http-equiv = "refresh" content = "1" />
<?php } } } ?>



<?php if(isset($_POST['amute'])) {
	
if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) {
Config::gotoPage('profile/'.$profile->username.'',0,'danger','You are not allowed to punish higher admins!');	
} else { 	
	
$areason = htmlspecialchars($_POST['areason']);
$atime = htmlspecialchars($_POST['atime']);
		
if(!$_POST['areason'] || !$_POST['atime']) {		
	
echo '<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> You left fields blank (reason).
			</div>';	} else { 	

$que = Config::$g_con->prepare('INSERT INTO `panel_sanctions` (`UserName`,`UserID`,`AdminName`,`AdminID`,`Type`,`Time`,`Reason`,`Date`) VALUES (?,?,?,?,?,?,?,?)');
$que->execute(array($profile->username, $profile->id,Config::getData("accounts","username",Config::getUser()),Config::getData("accounts","id",Config::getUser()), 2, $atime, $areason, date('d/m/Y H:i', $time)));

$sanctions = Config::$g_con->prepare('INSERT INTO `sanctions` (`Time`,`Player`,`By`,`Userid`,`Type`,`Reason`) VALUES (?,?,?,?,?,?)');
$sanctions->execute(array(date('d/m/Y H:i', $time), $profile->username, Config::getData("accounts","username",Config::getUser()), $profile->id, 0, $areason));
?>
<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">×</span>
				</button><i class="fa fa-info-circle"></i> The player was successfully sanctioned.
			</div>
<meta http-equiv = "refresh" content = "1" />
<?php } } } ?>
<?php } ?>


<?php if(Config::getData("accounts","admin",Config::getUser()) > 1) { ?>
<div id="sanctioneaza" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-cog"> </i> Sanction user</h5>
				<Hr>
				<form method="post">
							<input style="width: 100%;" class="form-control" placeholder="Reason" name="areason" type="text">
							<br>
							<input style="width: 100%;" class="form-control" placeholder="Time (999 for ban permanent)" name="atime" type="number">
							<br>
							<button class="btn btn-danger btn-block" name="aban" type="submit">Ban</button>
							<br>
							<button class="btn btn-danger btn-block" name="awarn" type="submit">Warn</button>
							<br>
							<!--<button class="btn btn-primary btn-block" name="amute" type="submit">Mute</button>
							<br>
							<button class="btn btn-primary btn-block" name="ajail" type="submit">Jail</button>
							<br>
							<button class="btn btn-primary btn-block" name="whitelist" type="submit">MTA Serial Whitelist</button>
							<br>-->
					</form>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php if(Config::getData("accounts","admin",Config::getUser()) > 0) { ?>
<div id="suspendeaza" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-remove"> </i> Suspend player</h5>
				<Hr>
				<form method="post">
							<input style="width: 100%;" class="form-control" placeholder="Reason" name="sreason" type="text">
							<br>
							<input style="width: 100%;" class="form-control" placeholder="Days (999 for permanent)" name="sdays" type="number">
							<br>
							<button class="btn btn-danger btn-block" name="psuspend" type="submit">Suspend</button>
					</form>
			</div>
		</div>
	</div>
</div>

<div id="unsuspendeaza" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<h5><i class="fa fa-cog"> </i> Unsuspend player</h5>
				<Hr>
				<form method="post">
							<center>Esti sigur ca vrei sa-i dai unsuspend lui <?php echo $profile->username ?>?</center>
							<br>
							<button class="btn btn-success btn-block" name="punsuspend" type="submit">Sunt sigur!</button>
					</form>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<div class="col-md-3">
    <div class="panel" style="min-height: 400px; margin-bottom:5px">
        <div class="panel-body" style="overflow:hidden">
          <!-- Profielfoto en skin -->
<a href="">
    <img id="profileAvatar" src="<?php echo ($profile->avatar); ?>" 
        style="width: 70%; margin-left: 25px;">
</a>


    <a href="">
        <img id="characterSkinImage" src="<?php echo Config::$_PAGE_URL; ?>assets/img/skins/Skin_<?php echo $characters[0]->skin; ?>.png" 
            style="width: 70%; margin-left: 25px;">
    </a>


<script>
    // Dynamisch wisselen tussen profielfoto en skin
    document.addEventListener('DOMContentLoaded', () => {
        const profileAvatar = document.getElementById('profileAvatar');
        const characterSkinImage = document.getElementById('characterSkinImage');

        if (characterSkinImage) {
            characterSkinImage.addEventListener('load', () => {
                if (profileAvatar) {
                    profileAvatar.style.display = 'none';
                }
            });
        }
    });
</script>


            <center>
                <?php echo ($profile->online ? '<span class="label label-success label-transparent">ONLINE</span>' : '<span class="label label-danger label-transparent">OFFLINE</span>')?>
                <h5><?php echo $profile->username; ?></h5><form method="post">
                <?php
				$wd = Config::$g_con->prepare('SELECT `ID`,`Color`,`Icon`,`Tag` FROM `wcode_functions` WHERE `UserName` = ?');
				$wd->execute(array($profile->username));
				if($wd->rowCount()) {
					$text = '';
					while($r_data = $wd->fetch(PDO::FETCH_OBJ)) {
						if(Config::isLogged() && Config::getData("accounts","admin",Config::getUser()))
							$text = $text . '<span class="label label-primary" style="background-color: '.$r_data->Color.'"> <i class="'.$r_data->Icon.'"></i> '.$purifier->purify(Config::xss(Config::clean($r_data->Tag))).' <button type="submit" name="delete_tag" value="'.$r_data->ID.'" style="background-color: #0000; border: 0px solid #fff; padding-left: 0px; padding-right: 0px;"><i class="fa fa-close"></i></button></span> ';
						else
							$text = $text . '<span class="label label-primary" style="background-color: '.$r_data->Color.'"> <i class="'.$r_data->Icon.'"></i> '.$purifier->purify(Config::xss(Config::clean($r_data->Tag))).'</span> ';
					}
					echo $text;
				}
				?></form>
				<tr><td>Joined on <?php echo Config::getData("accounts", "registerdate", $profile->id) ?></td></tr>
				
				<?php 
				if($profile->admin) echo '<span class="label label-primary" style="background-color: orange"> <i class="fa fa-shield"></i> Admin</span> ';
				if($profile->supporter) echo '<span class="label label-primary" style="background-color: #44bb35"> <i class="fa fa-gear"></i> Supporter</span> ';
				if($profile->credits) echo '<span class="label label-primary" style="background-color: #fff92a; color: #696969; border: 1px solid #e8d707;"> <i class="fa fa-star"></i> Premium</span> ';
				?>
			
			<?php if(Config::getUser() != $profile->username) { ?>
			<hr><a href="<?php echo ''.Config::$_PAGE_URL . 'complaints/create/' . $profile->username ?>"><button type="button" class="btn btn-danger" title="Create"><i class="ti-pencil"></i>Report Player</button></a>
			<?php } ?></br></br>
			<?php if(Config::isLogged(Config::getUser()) && Config::isAdmin(Config::getUser(),5)) echo '<button type="button" class="btn btn-danger" title="Create" data-toggle="modal" data-target="#sanctioneaza"><i class="ti-pencil"></i>Admin Actions</button></a>'; ?>
			</br></br>
			<?php if(Config::isLogged(Config::getUser()) or Config::isAdmin(Config::getUser(),5)) echo '<button type="button" class="btn btn-warning" title="Avatar" data-toggle="modal" data-target="#changeavatar"><i class="ti-pencil"></i>Change Avatar</button></a>'; ?>
			</br></br>
			<?php if(Config::isLogged(Config::getUser()) or Config::isAdmin(Config::getUser(),5)) echo '<button type="button" class="btn btn-warning" title="Serial" data-toggle="modal" data-target="#addserial"><i class="ti-pencil"></i>Add Serial</button></a>'; ?>
</div></div>
	<?php
	if(Config::isLogged() && Config::getData("accounts","admin",Config::getUser())) {
		if(isset($_POST['delete_tag'])) {
			$wcs = Config::$g_con->prepare('DELETE FROM `wcode_functions` WHERE `ID` = ?');
			$wcs->execute(array($_POST['delete_tag']));
			Config::gotoPage("profile/".$profile->username."",0,"success","The TAG has been removed!");
		}
		if(isset($_POST['insert']) && strlen($_POST['tag']) > 0) {
			$wcs = Config::$g_con->prepare('INSERT INTO `wcode_functions` (`UserID`,`UserName`,`Tag`,`Color`,`Icon`) VALUES (?,?,?,?,?)');
			$wcs->execute(array($profile->id,$profile->username,$_POST['tag'],$_POST['color'],$_POST['icon']));
			Config::gotoPage("profile/".$profile->username."",0,"success","New TAG has been setted!");
		}
		 
	?>
		<div class="panel">
			<div class="panel-body" style="overflow:hidden; padding-top:10px; padding-bottom:10px;">
				<center><form method="post">
					<?php
					if(Config::getData("accounts","admin",Config::getUser()) > 0) echo '<button type="button" class="btn btn-link active" data-toggle="modal" data-target="#tagadd"><i class="fa fa-code"> </i>Add TAG</button>';
					if(Config::getData("accounts","admin",Config::getUser()) > 0 && Config::getData("accounts","admin",$profile->id) <= 5) {
						$ban = Config::$g_con->prepare('SELECT * FROM `bans` WHERE `account` = ?');
						$ban->execute(array($profile->id));
						if(!$ban->rowCount())
							echo '';
						else
							echo '<button type="submit" name="unban" class="btn btn-link active" style="color: red"><i class="fa fa-legal"> </i>Unban player</button>';
					}
					if(!$profile->Muted)
						echo '';
					else
						echo '<button type="submit" name="unmute" class="btn btn-link active" style="color: green"><i class="fa fa-check"> </i>Unmute player</button>';
					
					?>
				</form></center>
			</div>
		</div>
	<?php
		
	}
	?>
</div>

<div class="col-md-9">
	<?php 
	$ban = Config::$g_con->prepare('SELECT * FROM `bans` WHERE `account` = ?');
	$ban->execute(array($profile->id));
	if($ban->rowCount()) { 
		$ba = $ban->fetch(PDO::FETCH_OBJ);
	?>
	<div class="alert alert-danger alert-dismissible" role="alert">
		<i class="fa fa-times-circle"></i> This account has been banned <?php echo ($ba->until ? 'temporary' : '<b>permanent</b>'); ?> by Admin <?php echo Config::formatName($ba->AdminName) ?> for <?php echo $purifier->purify(Config::xss(Config::clean($ba->Reason))) ?>. (<?php echo $ba->BanTimeDate ?>) 
		<?php if(Config::isLogged() && $ba->username == Config::getNameFromID(Config::getUser())) { ?>
			<a href="<?php echo Config::$_PAGE_URL ?>unban/create"><span class="label label-default label-transparent"><i class="fa fa-mail-forward"></i> UNBAN REQ</span></a>
		<?php } ?>
	</div>
	<?php } ?>
            </form>
            </center>
        </div>


<div class="col-md-9">
<div class="tab-content" style="background-color: #41555e">
    <ul class="nav nav-tabs" id="characterTabs" role="tablist">
        <?php
        // Haal alle karakernamen op die bij het account horen
        $account = Config::getUser(); // Haalt het huidige account op
        $query = Config::$g_con->prepare('SELECT * FROM `characters` WHERE `account` = ?');
        $query->execute(array($profile->id));
        $characters = $query->fetchAll(PDO::FETCH_OBJ);

        // Genereer de tabs voor elk karakter
        if ($characters) {
            $activeClass = 'active'; // De eerste tab is standaard actief
            foreach ($characters as $character) {
                echo '<li class="nav-item">
                        <a class="nav-link ' . $activeClass . '" id="tab_' . $character->charactername . '" data-toggle="tab" href="#' . $character->charactername . '" role="tab" data-skin="' . $character->skin . '">' . htmlspecialchars($character->charactername) . '</a>
                      </li>';
                $activeClass = '';  // Na de eerste tab, zijn de overige niet actief
            }
        } else {
            echo 'No characters found for this account.';
        }
        ?>
    </ul>

    <!-- Tabbladen die verschijnen nadat je een karakter kiest -->
<div class="tab-content" style="background-color: #41555e">
    <?php
    // Toon de gegevens voor elk karakter
    if ($characters) {
        $activeClass = 'show active'; // De eerste tab is standaard actief
        foreach ($characters as $character) {
            echo '<div class="tab-pane fade ' . $activeClass . '" id="' . $character->charactername . '" role="tabpanel">';
            ?>
            <ul class="nav nav-tabs" id="profileTabs_<?php echo $character->charactername; ?>" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile_<?php echo $character->charactername; ?>" role="tab">Profile</a></li>
                <li class="nav-item"><a class="nav-link" id="properties-tab" data-toggle="tab" href="#properties_<?php echo $character->charactername; ?>" role="tab">Properties</a></li>
                <li class="nav-item"><a class="nav-link" id="vehicles-tab" data-toggle="tab" href="#vehicles_<?php echo $character->charactername; ?>" role="tab">Vehicles</a></li>
                <li class="nav-item"><a class="nav-link" id="history-tab" data-toggle="tab" href="#history_<?php echo $character->charactername; ?>" role="tab">Faction History</a></li>
            <!--<?php 
		if(Config::isAdmin(Config::getUser())) echo '<li><a href="#ulog" role="tab" data-toggle="tab">Manage</a></li>';
		?>-->
			</ul>

            <div class="tab-content" style="background-color: #41555e">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile_<?php echo $character->charactername; ?>" role="tabpanel">
                    <table class="table table-minimal">
                        <thead>
                            <tr><th style="width:50%"></th><th style="width:50%"></th></tr>
                        </thead>
                        <tbody>
                            <form method="post">
							<tr><td><strong style="font-weight: 500;">Faction</strong></td><td><?php echo Config::factionName($character->id) ?></td></tr>

                                <tr>
                                    <td><strong style="font-weight: 500;">Faction Warns</strong></td>
                                    <td><?php echo Config::getData("accounts", "punishpoints", $character->id) ?>/3</td>
                                </tr>
                                <tr>
                                    <td><strong style="font-weight: 500;">Date of Birth</strong></td>
                                    <td><?php echo Config::getData("characters", "date_of_birth", $character->id) ?> (Age <?php echo Config::getData("characters", "age", $character->id) ?>)</td>
                                </tr>
								<tr>
                                    <td><strong style="font-weight: 500;">Height & Weight</strong></td>
                                    <td><?php echo Config::getData("characters", "height", $character->id) ?>CM <?php echo Config::getData("characters", "weight", $character->id) ?>KG</td>
                                </tr>
								
                                <tr><td><strong style="font-weight: 500;">Character made on </strong></td><td><?php echo Config::getData("characters", "creationdate", $character->id) ?></td></tr>
								<tr><td><strong style="font-weight: 500;">Last known area </strong></td><td><?php echo Config::getData("characters", "lastarea", $character->id) ?></td></tr>
								<?php if(Config::isLogged() && (Config::isAdmin(Config::getUser()) || $profile->id == Config::getUser())) { ?>
						<tr>
							<td><strong style="font-weight: 500;">Money</strong></td>
							<td>
								<?php echo Config::formatNumber($character->money) . ' / ' . Config::formatNumber($character->bankmoney)  ?>
								<?php if(Config::isLogged(Config::getUser()) && Config::isAdmin(Config::getUser(),5)) echo '<button type="button" class="btn btn-link active" data-toggle="modal" data-target="#moneym">Modify</button>'; ?>
							</td>
						</tr>
						<tr>
							<td><strong style="font-weight: 500;">Premium Points</strong></td>
							<td>
								<?php echo Config::getData("accounts","credits",$profile->id) ?>
								<?php if(Config::isLogged(Config::getUser()) && Config::isAdmin(Config::getUser(),5)) echo '<button type="button" class="btn btn-link active" data-toggle="modal" data-target="#premiumed">Modify</button>'; ?>
							</td>
						</tr>
						<tr>
							<td><strong style="font-weight: 500;">Email</strong></td>
							<td>
								<?php echo $purifier->purify(Config::xss(Config::clean(Config::getData("accounts","email",$profile->id)))) ?>
								<button type="button" class="btn btn-link active" data-toggle="modal" data-target="#small-modals">Modify</button>
							</td>
						</tr>
					<?php } ?>
					<tr><td><strong style="font-weight: 500;">Last login</strong></td><td><?php echo Config::timeAgo(Config::getData("characters","lastlogin",$character->id)) ?></td></tr>
					<tr>
						<td><strong style="font-weight: 500;">Warnings</strong></td>
						<td>
							<?php echo Config::getData("accounts","punishpoints",$profile->id) ?>/3
							<?php if(Config::isLogged(Config::getUser()) && Config::isAdmin(Config::getUser())) echo '<button type="button" class="btn btn-link active" value="Warnings" data-toggle="modal" data-target="#givewarn">Manage</button> <button type="submit" class="btn btn-link active" name="submit_action" value="Warnings">Reset</button>'; ?>
						</td>
					</tr>
				</form></tbody>
			</table>
		</div>

                <!-- Properties Tab -->
                <div class="tab-pane fade" id="properties_<?php echo $character->charactername; ?>" role="tabpanel">
                    <div class="col-md-6">
                        <?php
                        $wcode = Config::$g_con->prepare('SELECT * FROM `interiors` WHERE `owner` = ?');
                        $wcode->execute(array($character->id));
                        if (!$wcode->rowCount()) echo Config::csSN("warning", "This character owns no properties.", false);
                        else {
                            while ($house = $wcode->fetch(PDO::FETCH_OBJ)) {
                                echo '
						<div class="widget">
							<h5>House ID #'.$house->id.''; ?>
								<a onclick = "morty_ey(<?php echo $house->x; ?>,<?php echo $house->y; ?>)"><i class="fa fa-map-marker"></i></a> <?php
							echo '<h5>
							<hr style="margin-top: 2px">
							<p><b class="pull-right">'.$house->name.'</b>#Name</p>
							<p><b class="pull-right">'.Config::formatNumber($house->cost).'</b>#House Value</p>
							<p><b class="pull-right">'.($house->locked ? '<span class="label label-warning label-transparent">Locked</span>' : '<span class="label label-success label-transparent">Opened</span>').'</b>#Status</p>
						</div>
						';
                            }
                        }
                        ?>
                    </div>
               </div>


                <!-- Vehicles Tab -->
                <div class="tab-pane fade" id="vehicles_<?php echo $character->charactername; ?>" role="tabpanel">
    <?php
    // Bereid de query voor met een JOIN tussen vehicles en vehicles_shop
    $wcode = Config::$g_con->prepare('
        SELECT v.*, vs.vehbrand, vs.vehmodel 
        FROM `vehicles` v 
        LEFT JOIN `vehicles_shop` vs ON v.vehicle_shop_id = vs.id 
        WHERE v.owner = ?
    ');
    $wcode->execute(array($character->id));

    // Controleer of de gebruiker voertuigen heeft
    if (!$wcode->rowCount()) {
        echo Config::csSN("warning", "This character has no personal vehicles.", false);
    } else {
        // Begin met het weergeven van de voertuigen in een tabel
        echo '<table class="table table-minimal">
                <thead>
                    <tr>
                        <th>IMAGE</th>
                        <th>NAME</th>
                        <th>PRICE</th>
                        <th>KM</th>
                        <th>COLORS</th>
                        <th>VIP</th>
                    </tr>
                </thead>
                <tbody>';

        // Loop door alle voertuigen
        while ($car = $wcode->fetch(PDO::FETCH_OBJ)) {
            echo '
                <tr>
                    <td><img src="'.Config::$_PAGE_URL.'assets/img/vehicles/'.$car->model.'.jpg" style="height: 60px"></td>
                    <td>'. $car->vehbrand . ' ' . $car->vehmodel . '</td> <!-- Merk en Model van de voertuigen_shop tabel -->
                    <td>'.Config::formatNumber($car->vehprice).'</td>
                    <td>'.$car->odometer.'</td>
                    <td><font color="'.Config::$_vehColors[$car->color1].'">'.$car->color1.'</font> , <font color="'.Config::$_vehColors[$car->color2].'">'.$car->color2.'</font></td>
                    <td>'.($car->variant != "-" ? '<b>' . $car->variant . '</b>' : 'No').'</td>
                </tr>';
        }

        echo '</tbody></table>';
    }
    ?>
</div>


                <!-- Faction History Tab -->
                <div class="tab-pane fade" id="history_<?php echo $character->charactername; ?>" role="tabpanel">
                    <ul class="list-unstyled list-contacts">
                        <?php
                        $wcs = Config::$g_con->prepare('SELECT * FROM `factionlog` WHERE `player` = ? ORDER BY `id` DESC LIMIT 50');
                        $wcs->execute(array($character->id));
                        while ($fhistory = $wcs->fetch(PDO::FETCH_OBJ)) {
                            echo '
                                <li>
                                    <div class="media">
                                        <img src="'.Config::$_PAGE_URL.'assets/img/avatars/'.$character->skin.'.png" class="picture" alt="" style="border: 2px solid #79afbe">
                                    </div>
                                    <div class="info">
                                        <span class="username">'.$fhistory->action.'</span>
                                        <span class="email">'.Config::timeAgo($fhistory->time).'</span>
                                    </div>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <?php
            echo '</div>'; // Sluit het div van het karakter
            $activeClass = '';  // Na de eerste tab, zijn de overige niet actief
        }
    }
    ?>


<script>
    $(document).ready(function() {
        // Functie om de grootte van de tab aan te passen op basis van de inhoud
        function adjustTabHeights() {
            // Zoek alle tab-content elementen
            $('.tab-content .tab-pane').each(function() {
                var tabHeight = $(this).outerHeight(); // Haal de hoogte van de inhoud op
                var parentTab = $(this).closest('.tab-pane');
                
                // Stel de hoogte van de tab in (dit kan worden aangepast afhankelijk van je behoeften)
                parentTab.height(tabHeight + 50); // Voeg extra ruimte toe als nodig (bijv. padding)
            });
        }

        // Pas de tabhoogtes aan wanneer de pagina is geladen
        adjustTabHeights();
        
        // Herhaal de aanpassing elke keer als een andere tab wordt geactiveerd
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            adjustTabHeights(); // Pas de hoogte aan wanneer een tab wordt geopend
        });
    });
</script>




<script>
    $(document).ready(function () {
        // Zorg ervoor dat de tabs correct werken
        $('#characterTabs a').on('click', function (e) {
            e.preventDefault();  // Voorkom standaardgedrag van de link
            var targetTab = $(this).attr('href');  // Haal de link van de tab op

            // Verberg alle tab-inhoud
            $('.tab-pane').removeClass('show active');
            
            // Toon de inhoud van de aangeklikte tab
            $(targetTab).addClass('show active');
            
            // Update de skin afbeelding van het geselecteerde karakter
            var skin = $(this).data('skin');
            $('#characterSkinImage').attr('src', '<?php echo Config::$_PAGE_URL; ?>assets/img/skins/Skin_' + skin + '.png');
        });
    });
</script>

		<?php if(Config::isAdmin(Config::getUser())) { ?>
		<div class="tab-pane fade" id="ulog">
			<div class="col-md-8" style="padding-left: 4px; padding-right: 4px">
				<table class="table table-minimal">
					<thead>
						<tr>
							<th>LOG</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$jj = Config::$g_con->prepare("SELECT * FROM `wcode_logs` WHERE `UserName` = ? OR `VictimName` = ? ORDER BY `ID` DESC");
					$jj->execute(array($profile->username,$profile->username));
					while($logs = $jj->fetch(PDO::FETCH_OBJ)) {
						echo '<tr>
							<td>'.$logs->Log.' <small><i>('.$logs->Date.')</i></small></td>
						</tr>';
					}
					?>
					</tbody>
				</table>
			</div>
			<div class="col-md-4" style="padding-left: 4px; padding-right: 4px">
			<hr>
				<h5>Logs</h5>
				<hr>
				<li><a href="#" data-toggle="modal" data-target="#fulllogs">full logs</a></li>
				<li><a href="#" data-toggle="modal" data-target="#iplogs">ip logs</a></li>
				<hr>
				<h5>Others</h5>
				<hr>
				
<?php
$k = Config::$g_con->prepare("SELECT * FROM `wcode_suspend` WHERE `UserID` = ?");
$k->execute(array($profile->id));
$aresuspend = $k->rowCount();
?>				
				
				
<?php if(Config::getData("accounts","admin",Config::getUser()) > 1) { ?>	
<?php if (Config::getData("accounts","username",Config::getUser()) == $profile->username) { ?>	
<?php } else { ?>
<?php if(Config::isAdmin($profile->id) && Config::getData("accounts","admin",$profile->id) > Config::getData("accounts","admin",Config::getUser())) { ?>
<?php } else { ?>
	<li><a href="" data-toggle="modal" data-target="#sanctioneaza">sanction player</a></li>
	
<?php if ($aresuspend == 0) { ?>	
<li><a href="" data-toggle="modal" data-target="#suspendeaza">suspend panel</a></li>
<?php } else { ?>
<li><a href="" data-toggle="modal" data-target="#unsuspendeaza">unsuspend</a></li>
<?php } ?>	

<?php } ?>
<?php } ?>	
<?php } ?>			
			</div>
			<div class="clearfix"></div>
		</div>
		<?php } ?>
	</div>
</div>
<?php if(Config::isAdmin(Config::getUser())) { ?>
<div id="fulllogs" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-large" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<ul class="list-unstyled list-contacts">
				<?php
				$pl = Config::$g_con->prepare('SELECT * FROM `logs` WHERE `Userid` = ? ORDER BY `ID` DESC LIMIT 50');
				$pl->execute(array($profile->id));
				while($plogs = $pl->fetch(PDO::FETCH_OBJ)) {
					echo '
						<li>
							<div class="info" style="width: 90%">
								<span class="username">'.$plogs->Text.'</span>
								<span class="email">'.$plogs->Date.'</span>
							</div>';
						echo '</li>';
				}
				if(!$pl->rowCount()) echo "<center><small><i>No recent logs</i></small></center>";
				?>
				</ul>
			</div>
		</div>
	</div>
</div>

<div id="iplogs" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
	<div class="modal-dialog modal-large" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<ul class="list-unstyled list-contacts">
				<?php
				$pl = Config::$g_con->prepare('SELECT * FROM `iplogs` WHERE `playerid` = ? ORDER BY `ID` DESC LIMIT 50');
				$pl->execute(array($profile->id));
				while($plogs = $pl->fetch(PDO::FETCH_OBJ)) {
					echo '
						<li>
							<div class="info" style="width: 90%">
								<span class="username">'.$plogs->ip.'</span>
								<span class="email">'.$plogs->time.'</span>
							</div>';
						echo '</li>';
				}
				if(!$pl->rowCount()) echo "<center><small><i>No recent logs</i></small></center>";
				?>
				</ul>
			</div>
		</div>
	</div>
</div>

<?php } ?>
<script src="<?php echo Config::$_PAGE_URL; ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?php echo Config::$_PAGE_URL; ?>assets/js/map.min.js"></script>
<script>
$(function()
{
	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();
});
</script>

<style>
.tabs {
    font-family: Arial, sans-serif;
}
.tab-list {
    display: flex;
    list-style: none;
    padding: 0;
}
.tab {
    cursor: pointer;
    padding: 10px 20px;
    background: #f0f0f0;
    margin-right: 5px;
    border-radius: 5px 5px 0 0;
}
.tab.active {
    background: #ffffff;
    border-bottom: 2px solid #ffffff;
}
.tab-content {
    border: 1px solid #f0f0f0;
    border-top: none;
    padding: 20px;
}
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}
</style>

<script>
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
    });
});
</script>
