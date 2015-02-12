<?php
require 'dbconfig.php';
class User {

    function checkUser($uid, $oauth_provider, $username,$email,$twitter_otoken,$twitter_otoken_secret) 
	{
        $query = mysqli_query($db,"SELECT * FROM `users` WHERE oauth_uid = '$uid' and oauth_provider = '$oauth_provider'");
        $result = mysqli_fetch_array($query,MYSQLI_ASSOC);
        if (!empty($result)) {
            # User is already present
        } else {
            #user not present. Insert a new Record
            $query = mysqli_query($db,"INSERT INTO `users` (oauth_provider, oauth_uid, username,email,twitter_oauth_token,twitter_oauth_token_secret) VALUES ('$oauth_provider', $uid, '$username','$email')");
            $query = mysqli_query($db,"SELECT * FROM `users` WHERE oauth_uid = '$uid' and oauth_provider = '$oauth_provider'");
            $result = mysqli_fetch_array($query,MYSQLI_ASSOC);
            return $result;
        }
        return $result;
    }

}
?>
