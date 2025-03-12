<?php

use function PHPUnit\Framework\returnValue;

include_once('ProfileManager.php');

class OpenIdProfileManager extends ProfileManager{

	public function authenticate($sub='', $provider=''){
		$status = false;
		unset($_SESSION['userrights']);
		unset($_SESSION['userparams']);
        $status = $this->authenticateUsingOidSub($sub, $provider);
        if($status){
            if(strlen($this->displayName) > 15) $this->displayName = $this->userName;
            if(strlen($this->displayName) > 15) $this->displayName = substr($this->displayName,0,10).'...';
            $this->reset();
            $this->setUserRights();
            $this->setUserParams();
            if($this->rememberMe) $this->setTokenCookie();
            if(!isset($GLOBALS['SYMB_UID']) || !$GLOBALS['SYMB_UID']){
                $this->resetConnection();
                $sql = 'UPDATE users SET lastLoginDate = NOW() WHERE (uid = ?)';
                if($stmt = $this->conn->prepare($sql)){
                    $stmt->bind_param('i', $this->uid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
		return $status;
	}

	private function authenticateUsingOidSub($sub, $provider){
		$status = false;
		if($sub && $provider){
            $sql = 'SELECT uid from usersthirdpartyauth WHERE subUuid = ? AND provider = ?';
            if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('ss', $sub, $provider)){
					$stmt->execute();
					$stmt->bind_result($this->uid);
                    $stmt->fetch();
					$stmt->close();
				}
				else echo 'error binding parameters: '.$stmt->error;
			}
            if($this->uid){
                $sql = 'SELECT uid, firstname, username FROM users WHERE (uid = ?)';
                if($stmt = $this->conn->prepare($sql)){
                    if($stmt->bind_param('i', $this->uid)){
                        $stmt->execute();
                        $stmt->bind_result($this->uid, $this->displayName, $this->userName);
                        if($stmt->fetch()) $status = true;
                        $stmt->close();
                    }
                    else echo 'error binding parameters: '.$stmt->error;
                }
                else echo 'error preparing statement: '.$this->conn->error;
            }
		}
		return $status;
	}

	public function linkLocalUserOidSub($email, $sub, $provider, $username, $firstname, $lastname){
		if($email && $sub && $provider){
            $sql = 'SELECT u.uid, oid.subUuid, oid.provider from users u LEFT join usersthirdpartyauth oid ON u.uid = oid.uid 
			WHERE u.email = ?';
            if($stmt = $this->conn->prepare($sql)){
				if($stmt->bind_param('s', $email)){
					$stmt->execute();
					$results = mysqli_stmt_get_result($stmt);
					$stmt->close();
				}
				if ($results->num_rows < 1){
					//user does not exist in user table, create user
					$username = $username ?? $email;
					$firstname = $firstname ?? $email;
					$lastname = $lastname ?? '';
					$sql = 'INSERT INTO users (firstName, lastName, email, username) VALUES(?,?,?,?)';
					$this->resetConnection();
					if($stmt = $this->conn->prepare($sql)) {
						$stmt->bind_param('ssss', $firstname, $lastname, $email, $username);
						$stmt->execute();
					}
					$uid = $this->conn->insert_id;
					$sql = 'INSERT INTO usersthirdpartyauth (uid, subUuid, provider) VALUES(?,?,?)';
					$this->resetConnection();
					if($stmt = $this->conn->prepare($sql)) {
						$stmt->bind_param('iss', $uid, $sub, $provider);
						$stmt->execute();
					}
					return true;
				}
				else {
					$row = $results->fetch_array(MYSQLI_ASSOC);
					//found existing user. add 3rdparty auth info
					//email in users should be unique - that way only one user gets added per login
					$sql = 'INSERT INTO usersthirdpartyauth (uid, subUuid, provider) VALUES(?,?,?)';
					$this->resetConnection();
					if($stmt = $this->conn->prepare($sql)) {
						$stmt->bind_param('iss', $row['uid'], $sub, $provider);
						$stmt->execute();
					}
					$this->uid = $row['uid'];
					return true;
				}
			}
		}
	}
}
