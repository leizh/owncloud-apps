<?php

class OC_User_xmpp_Hooks {
	static public function createXmppSession($params){
		$xmpplogin=new OC_xmpp_login($params['uid'],'acs.li',$params['password'],OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));
		$xmpplogin->doLogin();
                
		$stmt = OCP\DB::prepare('SELECT ocUser FROM *PREFIX*xmpp WHERE ocUser = "'.$params['uid'].'"');
                $result = $stmt->execute();
                if($result->numRows()!=0){
			OC_User_xmpp_Hooks::deleteXmppSession();
                }
                $stmt = OCP\DB::prepare('INSERT INTO *PREFIX*xmpp (ocUser,jid,rid,sid) VALUES ("'.$params['uid'].'","'.$xmpplogin->jid.'","'.$xmpplogin->rid.'","'.$xmpplogin->sid.'")');
                $result=$stmt->execute();

	}

	static public function deleteXmppSession(){
		$stmt = OCP\DB::prepare('DELETE FROM *PREFIX*xmpp WHERE ocUser = "'.OCP\User::getUser().'"');
		$stmt->execute();
	}

	static public function createXmppUser($info){
		system('sudo /usr/sbin/ejabberdctl register '.$info['uid'].' acs.li '.$info['password']);
	}

	static public function updateXmppUserPassword($info){
		system('sudo /usr/sbin/ejabberdctl change_password '.$info['uid'].' acs.li '.$info['password']);
	}

	static public function post_updateVCard($id){
		if(OC_Preferences::getValue(OC_USER::getUser(),'xmpp','autoroster')!=true){ return false; }
		$email='';
		$vcardq=OC_Contacts_Vcard::find($id);
		if($vcardq==false)return false;
		$name=$vcardq['fullname'];
		$data=$vcardq['carddata'];
		$vcard = OC_VObject::parse($data);
		foreach($vcard->children as &$property) {
			if($property->name == 'EMAIL'){
				$email = $property->value;
			}
		}
		if($email!=''){
			$xmpplogin=new OC_xmpp_login(OCP\Config::getAppValue('xmpp', 'xmppAdminUser',''),'acs.li',OCP\Config::getAppValue('xmpp', 'xmppAdminPasswd',''),OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));	
			$xmpplogin->doLogin();
			$passwd=$xmpplogin->getUserPasswd(OCP\User::getUser().'@acs.li');

			$xuser=new OC_xmpp_login(OCP\User::getUser(),'acs.li',$passwd,OCP\Config::getAppValue('xmpp', 'xmppBOSHURL',''));
			$xuser->doLogin();
			$xuser->addRoster($email,$name);

		}
	}
}

?>