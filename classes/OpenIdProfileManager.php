<?php

use function PHPUnit\Framework\returnValue;

include_once('ProfileManager.php');

class OpenIdProfileManager extends ProfileManager
{

	public function authenticate($sub = '', $provider = '')
	{
		$status = false;
		unset($_SESSION['userrights']);
		unset($_SESSION['userparams']);
		$status = $this->authenticateUsingOidSub($sub, $provider);
		if ($status) {
			if (strlen($this->displayName) > 15) $this->displayName = $this->userName;
			if (strlen($this->displayName) > 15) $this->displayName = substr($this->displayName, 0, 10) . '...';
			$this->reset();
			$this->setUserRights();
			$this->setUserParams();
			// if($this->rememberMe) $this->setTokenCookie();
			if (!isset($GLOBALS['SYMB_UID']) || !$GLOBALS['SYMB_UID']) {
				$this->resetConnection();
				$sql = 'UPDATE users SET lastLoginDate = NOW() WHERE (uid = ?)';
				if ($stmt = $this->conn->prepare($sql)) {
					$stmt->bind_param('i', $this->uid);
					$stmt->execute();
					$stmt->close();
				}
			}
		}
		return $status;
	}

	private function authenticateUsingOidSub($sub, $provider)
	{
		$status = false;
		if ($sub && $provider) {
			$sql = 'SELECT uid from usersthirdpartyauth WHERE subUuid = ? AND provider = ?';
			if ($stmt = $this->conn->prepare($sql)) {
				if ($stmt->bind_param('ss', $sub, $provider)) {
					$stmt->execute();
					$stmt->bind_result($this->uid);
					$stmt->fetch();
					$stmt->close();
				} else echo 'error binding parameters: ' . $stmt->error;
			}
			if ($this->uid) {
				$sql = 'SELECT uid, firstname, username FROM users WHERE (uid = ?)';
				if ($stmt = $this->conn->prepare($sql)) {
					if ($stmt->bind_param('i', $this->uid)) {
						$stmt->execute();
						$stmt->bind_result($this->uid, $this->displayName, $this->userName);
						if ($stmt->fetch()) $status = true;
						$stmt->close();
					} else echo 'error binding parameters: ' . $stmt->error;
				} else echo 'error preparing statement: ' . $this->conn->error;
			}
		}
		return $status;
	}

	public function linkThirdPartySid($thirdparty_sid, $local_sid, $ip)
	{
		if (empty($thirdparty_sid)) return;
		//neon edit
		$this->conn->query("
			DELETE FROM usersthirdpartysessions
			WHERE timestamp < NOW() - INTERVAL 24 HOUR
		");
		$sql = 'INSERT INTO usersthirdpartysessions(thirdparty_id, localsession_id, ipaddr)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE thirdparty_id = thirdparty_id';
		//end neon edit
		if ($stmt = $this->conn->prepare($sql)) {
			if ($stmt->bind_param('sss', $thirdparty_sid, $local_sid, $ip)) {
				$stmt->execute();
				//if($stmt->error){
				//}
				$stmt->close();
			}
		}
	}

	public function linkLocalUserOidSub($email, $sub, $provider, $user_id, $given_name, $family_name)
	{
		//neon edit
		if ($email && $sub && $provider) {
			$sql = 'SELECT u.uid from users u WHERE u.email = ?';
			if ($stmt = $this->conn->prepare($sql)) {
				if ($stmt->bind_param('s', $email)) {
					$stmt->execute();
					$results = mysqli_stmt_get_result($stmt);
					$stmt->close();
				}
				//if no user found or more than 1 user, create new user
				if ($results->num_rows != 1) {
				
					// create local user
					$sql = 'INSERT INTO users (email, firstname, lastname, username) VALUES (?,?,?,?)';
					$this->resetConnection();
				
					if ($stmt = $this->conn->prepare($sql)) {
						if ($family_name === null || $family_name === '') {
								$given_name  = 'NEON';
								$family_name = 'Account';
							}
						$stmt->bind_param('ssss', $email, $given_name, $family_name, $user_id);
						$stmt->execute();
						$newUid = $this->conn->insert_id;
						$stmt->close();
					} else {
						throw new Exception("Failed to create local user");
					}
				
					// link third party auth
					$sql = 'INSERT INTO usersthirdpartyauth (uid, subUuid, provider) VALUES (?,?,?)';
				
					if ($stmt = $this->conn->prepare($sql)) {
						$stmt->bind_param('iss', $newUid, $sub, $provider);
						$stmt->execute();
						$stmt->close();
					} else {
						throw new Exception("Failed to link third party auth");
					}
				
					$this->uid = $newUid;
					return true;
				// if only one user found, link
				} else if ($results->num_rows == 1) {
					$row = $results->fetch_array(MYSQLI_ASSOC);
					//found existing user. add 3rdparty auth info
					$sql = 'INSERT INTO usersthirdpartyauth (uid, subUuid, provider) VALUES(?,?,?)';
					$this->resetConnection();
					if ($stmt = $this->conn->prepare($sql)) {
						$stmt->bind_param('iss', $row['uid'], $sub, $provider);
						$stmt->execute();
					}
					$this->uid = $row['uid'];
					return true;
				}
			}
		}
	}
	
	//neon edit; add function to update user table with Auth0 values on login
	public function updateLocalUserFromAuth0Metadata($sub, $provider, $firstName, $lastName, $institution, $affiliation, $country, $subjectMatterExpertise, $orcid)
	{
		if (!$sub || !$provider) return false;
		// ROR lookup
		if ($institution) {
			$originalInstitution = $institution;
		
			$url = "https://api.ror.org/v2/organizations/" . urlencode($institution);
			$response = @file_get_contents($url);
		
			if ($response !== false) {
				$ror = json_decode($response, true);
		
				if (!empty($ror['names'])) {
					foreach ($ror['names'] as $name) {
						if (in_array('ror_display', $name['types'], true)) {
							$institution = $name['value'];
							break;
						}
					}
		
					// assumes instutition is an organization and not an ror
					if ($institution === $originalInstitution && isset($ror['names'][0]['value'])) {
						$institution = $ror['names'][0]['value'];
					}
				}
			}
		}
		$sql = '
			SELECT uid
			FROM usersthirdpartyauth
			WHERE subUuid = ? AND provider = ?
			LIMIT 1
		';
	
		if (!$stmt = $this->conn->prepare($sql)) {
			throw new Exception("Failed to prepare user lookup");
		}
	
		$stmt->bind_param('ss', $sub, $provider);
		$stmt->execute();
		$result = mysqli_stmt_get_result($stmt);
		$stmt->close();
	
		if (!$result || $result->num_rows < 1) {
			return false;
		}
	
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$uid = $row['uid'];
	
		$sql = '
			UPDATE users
			SET firstname = ?,
				lastname = ?,
				institution = ?,
				affiliation = ?,
				country = ?,
				subject_matter_expertise_provider = ?,
				guid = ?
			WHERE uid = ?
		';
	
		if (!$stmt = $this->conn->prepare($sql)) {
			throw new Exception("Failed to prepare user metadata update");
		}
	
		$subjectMatterExpertise = $subjectMatterExpertise === ''
			? null
			: $subjectMatterExpertise;
	
		$stmt->bind_param(
			'sssssssi',
			$firstName,
			$lastName,
			$institution,
			$affiliation,
			$country,
			$subjectMatterExpertise,
			$orcid,
			$uid
		);
	
		$status = $stmt->execute();
		$stmt->close();
	
		return $status;
	}
	//end neon edit

	public function lookupLocalSessionIDWithThirdPartySid($thirdparty_sid)
	{
		$sql = 'SELECT localsession_id FROM usersthirdpartysessions WHERE thirdparty_id = ?';
		$localSessionID = '';
		if ($stmt = $this->conn->prepare($sql)) {
			if ($stmt->bind_param('s', $thirdparty_sid)) {
				$stmt->execute();
				$stmt->bind_result($localSessionID);
				$stmt->fetch();
				$stmt->close();
			}
		}
		return $localSessionID;
	}

	public function removeThirdPartySid($local_sid, $thirdparty_sid)
	{
		//$sql = 'DELETE FROM usersthirdpartysessions WHERE thirdparty_id = ? AND localsession_id = ? LIMIT 1';
		//if ($stmt = $this->conn->prepare($sql)) {
		//	if ($stmt->bind_param('ss', $thirdparty_sid, $local_sid)) {
		//		$stmt->execute();
		//		$stmt->close();
		//	}
		//}
		//neon edit
		$sql = 'DELETE FROM usersthirdpartysessions WHERE localsession_id = ? LIMIT 1';
		$this->resetConnection();
		if ($stmt = $this->conn->prepare($sql)) {
			if ($stmt->bind_param('s', $local_sid)) {
				$stmt->execute();
				$stmt->close();
			}
		}
		//end edit
	}

	public function forceLogout($targetSessionId, $thirdparty_sid)
	{
		if(!empty($targetSessionId)){
			$this->removeThirdPartySid($targetSessionId, $thirdparty_sid);
		}		
		session_write_close();
		session_id($targetSessionId);
		session_start();
		$_SESSION['force_logout'] = true;
	}
}
