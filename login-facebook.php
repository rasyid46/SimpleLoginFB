<?php
 
require 'facebook/facebook.php';
require 'config/fbconfig.php';
require 'config/functions.php';
 
 
$config = array(
      'appId' => APP_ID,
      'secret' => APP_SECRET,
      'fileUpload' => false, // optional
	  'cookie' => true,
     'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
  );	
	$facebook = new Facebook($config);
	$access_token = $facebook->getAccessToken();
	
   $user       = $facebook->getUser();
   
   $loginUrl   = $facebook->getLoginUrl(
            array(
               'scope'         => 'email,offline_access,publish_stream,user_birthday,user_location,user_work_history,user_about_me,user_hometown',  
                
            )
    );
 $access_token = $facebook->getAccessToken();
		
		//
		  
if ($user) {
  
      // We have a user ID, so probably a logged in user.
      // If not, we'll get an exception, which we handle below.
      try {

        $user_profile = $facebook->api('/me','GET');
		 var_dump($user_profile);
		$id= $user_profile['id'];
		$name=$user_profile['name'];
		$email = $user_profile['email'];
		$firs_name= $user_profile['first_name'];
		$last_name=  $user_profile['last_name'];
		$gender =  $user_profile['gender'];
        echo "Id: " . $id.'<br>';
        echo "Name: " . $name.'<br>';
        echo "email: " .$email.'<br>';
        echo "first_name: " .$first_name .'<br>';
        echo "last_name: " .$last_name.'<br>';
        echo "gender: " .$gender.'<br>';
			$image = 'https://graph.facebook.com/'.$id.'/picture?width=300';
		 $_SESSION['id'] = $id;
			$_SESSION['oauth_id'] = $id;
            $_SESSION['username'] =$name;
			$_SESSION['email'] = $email;
            $_SESSION['oauth_provider'] = $id;
            $_SESSION['image'] = $image;
            $_SESSION['profile'] = $user_profile;
			echo "<img src='$image' /><br><br>";
         header("Location: home.php");
			 
		 

		 
		
		 
		 
		 
      } catch(FacebookApiException $e) {
       
        error_log($e->getType());
        error_log($e->getMessage());
      }   
	  
	  
    } else {

    
    //  $login_url = $facebook->getLoginUrl();
	  
	$login_url= $facebook->getLoginUrl( array(
	'scope'         => 'email,offline_access,publish_stream,user_birthday,user_location,user_work_history,user_about_me,user_hometown',       
	//'scope'         => 'email,user_work_history',       
	));
	 
	 $access_token = $facebook->getAccessToken();
      //echo 'Please <a href="' . $login_url . '">login.</a>';
		//otomati redirect
			ob_start(); // ensures anything dumped out will be caught

			// do stuff here
			$url = $login_url;

				// clear out the output buffer
				while (ob_get_status()) 
				{
					ob_end_clean();
				}

				// no redirect
				header( "Location: $url" );
    }
	 
 
		 //

  ?>

  </body>
</html>