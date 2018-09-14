<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

class Blacklist
{

    /**
     * Check if a email or user (by email) is in blacklist and the reason if exists.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} Email to check in blacklist<br/>
     * <p><strong>Returns:</strong><br/>
     * Type (whitelist, blacklist) and the reason if is in blacklisted.
     * </p>
     */
    public static function blacklistedEmailInfo($email=''){
        if($email == ''){
            $email = $_REQUEST['email'];
        }
        if ($email == '') {
            Response::outputErrorMessage('Email param is empty');
        }
        $response = new Response();

        $sql = "SELECT ". $GLOBALS['tables']['user_blacklist'] . ".email, added, `data` as reason FROM "
            . $GLOBALS['tables']['user_blacklist'] . " INNER JOIN ".$GLOBALS['tables']['user_blacklist_data']
            . " ON ".$GLOBALS['tables']['user_blacklist'] . ".email=".$GLOBALS['tables']['user_blacklist_data'] .".email"
            ." WHERE ".$GLOBALS['tables']['user_blacklist'].".email = :email"
            . "
			UNION
			(
				SELECT email, null, 'Blacklist by profile user'
				FROM " . $GLOBALS['tables']['user'] . " WHERE blacklisted=1 AND email = :email
			)
			LIMIT 1
			"
        ;
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ); // only first coincidence.
            if($result){
                $response->setData('blacklist', $result); // type attribute == 'blacklist'
            } else {
                $result = array(
                    'email' => $email
                );
                $response->setData('whitelist', $result); // type attribute == 'whitelist'
            }
            $db = null;
            $response->output();
        } catch(\PDOException $e) {
            Response::outputError($e);
        }
        die(0);
    }

    /**
     * Remove an email from blacklist if it is blacklisted
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} Email to remove from blacklist<br/>
     * <p><strong>Returns:</strong><br/>
     * Messages with the actions executed.
     * </p>
     */
    public static function removeEmailFromBlacklist($email = ''){

        if($email == ''){
            $email = $_REQUEST['email'];
        }
        if ($email == '') {
            Response::outputErrorMessage('Email param is empty');
        }
        $db = PDO::getConnection();
        $message_arr = array();
        $response = new Response();
        $sql = "UPDATE ". $GLOBALS['tables']['user'] . " SET blacklisted = '0' WHERE email = :email";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
            $stmt->execute();
            $message_arr[] = 'User with email '.$email.' is no longer blacklisted if it was previously.';
        } catch(\Exception $e) {
            Response::outputError($e);
        }
        $sql = "DELETE FROM ". $GLOBALS['tables']['user_blacklist'] . " WHERE email = :email";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
            $stmt->execute();
            $message_arr[] = 'Email '.$email.' is no longer blacklisted if it was previously.';
        } catch(\Exception $e) {
            Response::outputError($e);
        }
        $sql = "DELETE FROM ". $GLOBALS['tables']['user_blacklist_data'] . " WHERE email = :email";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
            $stmt->execute();
            $db = null;
            $message_arr[] = 'Reason for being blacklisted has been removed if it existed before.';
        } catch(\Exception $e) {
            Response::outputError($e);
        }

        if(count($message_arr) == 0){
            $message_arr[] = 'No message';
        }


        $response->setData('remove_blacklist', $message_arr);
        $response->output();

    }

    /**
     * Adds an email to blacklist
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} Email to remove from blacklist<br/>
     * <p><strong>Returns:</strong><br/>
     * Messages with the actions executed.
     * </p>
     */
    public static function addEmailToBlacklist($email = ''){
		if($email == ''){
            $email = $_REQUEST['email'];
        }
		
        if ($email == '') {
            Response::outputErrorMessage('Email param is empty');
        }
		
		$db = PDO::getConnection();
        $response = new Response();
		
		$sql = sprintf( "SELECT %s.id FROM %s WHERE %s.email = :email AND %s.blacklisted = '0' ",
			$GLOBALS['tables']['user'],
			$GLOBALS['tables']['user'],
			$GLOBALS['tables']['user'],
			$GLOBALS['tables']['user']
		);
		
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
            $stmt->execute();
			$result = $stmt->fetch( PDO::FETCH_OBJ );
			
        } catch(\Exception $e) {
            Response::outputError($e);
        }
		
		/* Email already blacklisted */
		if( !$result ) {
			$response->setData('add_blacklist', []);
			
			$db = null;
			$response->output();
			die(0);
		}
		
		/* Add to Blacklist */
		$response->setData('add_blacklist', $result);
		
		$sqlUser = sprintf( "UPDATE %s SET blacklisted = '1' WHERE %s.email = :email",
			$GLOBALS['tables']['user'],
			$GLOBALS['tables']['user']
		);
		
		$sqlUserBlacklist = sprintf( "INSERT INTO %s ( email, added ) VALUES ( :email, NOW() )",
			$GLOBALS['tables']['user_blacklist']
		);
		
		$sqlUserBlacklistData = sprintf( "INSERT INTO %s ( email, name, data ) VALUES ( :email, 'reason', 'Blacklisted by API' )",
			$GLOBALS['tables']['user_blacklist_data']
		);
		
		try {
			$stmt = $db->prepare( $sqlUser );
			$stmt->bindParam( 'email', $email, PDO::PARAM_STR );
			$success = $stmt->execute();

			if( !$success ) {
				Response::outputError( 'Could not blacklist user' );
			}
			
			$stmt = $db->prepare( $sqlUserBlacklist );
			$stmt->bindParam( 'email', $email, PDO::PARAM_STR );
			$success = $stmt->execute();
			
			if( !$success ) {
				Response::outputError( 'Could not set user blacklisted added time' );
			}
			
			$stmt = $db->prepare( $sqlUserBlacklistData );
			$stmt->bindParam( 'email', $email, PDO::PARAM_STR );
			$success = $stmt->execute();
			
			if( !$success ) {
				Response::outputError( 'Could not set user blacklisted reason' );
			}
			
			$db = null;
			$response->output();
			
        } catch(\Exception $e) {
            Response::outputError($e);
        }
		
        die(0);
	}
}
