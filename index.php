<?php
if (isset($_POST['tag']) && $_POST['tag'] != '') {
    // Get tag
    $tag = $_POST['tag'];

    // Include Database handler
    require_once 'db.php';	    
	require_once 'PHPMailerAutoload.php';
	$db = new DBHelper();
	
    // response Array
    $response = array("tag" => $tag, "success" => 0, "error" => 0);

    // check for tag type
    if ($tag == 'login') {
        // Request type is check Login
        $email = $_POST['email'];
        $password = $_POST['password'];

        // check for user
        $user = $db->getUserByEmailAndPassword($email, $password);
        if ($user != false) {
            // user found
			
			// echo json with success = 1
			$response["success"] = 1;
			$response["user"]["fname"] = $user["firstname"];
			$response["user"]["lname"] = $user["lastname"];
			$response["user"]["email"] = $user["email"];
			$response["user"]["deactive"] = $user["deactive"];
			$response["user"]["created_at"] = $user["created_at"];
			
			echo json_encode($response);
			
        } else {
            // user not found
            // echo json with error = 1
            $response["error"] = 1;
            $response["error_msg"] = "Email or password incorrect!";
            echo json_encode($response);
        }
    } 
	else if ($tag == 'deactivate') {
        // Request type is deactivate
        $email = $_POST['email'];

        // check for user
        $user = $db->deactivate($email);
        if ($user != false) {
            // echo json with success = 1
            $response["success"] = 1;            
            echo json_encode($response);
        } else {
            // echo json with error = 1
            $response["error"] = 1;
            echo json_encode($response);
        }
    } 
	else if ($tag == 'activate') {
        // Request type is deactivate
        $email = $_POST['email'];

        // check for user
        $user = $db->activate($email);
        if ($user != false) {
            // echo json with success = 1
            $response["success"] = 1;            
            echo json_encode($response);
        } else {
            // echo json with error = 1
            $response["error"] = 1;
            echo json_encode($response);
        }
    } 
	else if ($tag == 'chgpass'){
		$email = $_POST['email'];
		$newpassword = $_POST['newpas'];  

		$hash = $db->hashSSHA($newpassword);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"];
		$subject = "Change Password Notification";
        $message = "Hello User,\n\nYour Password is sucessfully changed.\n\nRegards,\nAdmistration";
        $from = "qhubbandroid@gmail.com";
        $headers = "From:" . $from;
		if ($db->isUserExisted($email)) {
			$user = $db->forgotPassword($email, $encrypted_password, $salt);
			if ($user) {
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true; 
				
				$mail->SMTPSecure = 'ssl'; 
				$mail->Host = 'smtp.gmail.com';
				$mail->Port = 465;  
				$mail->Username = $from;  
				$mail->Password = "qhubbapp";   
					
				$mail->SetFrom($from, $from);
				$mail->Subject = $subject;
				$mail->Body = $message;
				$mail->AddAddress($email);
				if(!$mail->Send()) {
					$response["error"] = 1;
					echo json_encode($response);	
				} else {
					$response["success"] = 1;
					echo json_encode($response);
				}
			}
			else {
				$response["error"] = 1;
				echo json_encode($response);
			}
            // user is already existed - error response
		} else {
			$response["error"] = 2;
            $response["error_msg"] = "User not exist";
            echo json_encode($response);
		}
	}else if ($tag == 'forpass'){	
		
		$forgotpassword = $_POST['forgotpassword'];
		$randomcode = $db->random_string();
		$hash = $db->hashSSHA($randomcode);
		$encrypted_password = $hash["encrypted"]; // encrypted password
		$salt = $hash["salt"];
		$subject = "Password Recovery";
		$message = "Hello User,\n\nYour Password is sucessfully changed. Your new Password is $randomcode . Login with your new Password and change it in the User Panel.\n\nRegards,\nAdministration";
		$from = "qhubbandroid@gmail.com";
		$headers = "From:" . $from;
		if ($db->isUserExisted($forgotpassword)) {
			$user = $db->forgotPassword($forgotpassword, $encrypted_password, $salt);
			if ($user) {
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true; 
				
				$mail->SMTPSecure = 'ssl'; 
				$mail->Host = 'smtp.gmail.com';
				$mail->Port = 465;  
				$mail->Username = $from;  
				$mail->Password = "qhubbapp";   
					
				$mail->SetFrom($from, $from);
				$mail->Subject = $subject;
				$mail->Body = $message;
				$mail->AddAddress($forgotpassword);
				if(!$mail->Send()) {
					$response["error"] = 1;
					echo json_encode($response);	
				} else {
					$response["success"] = 1;
					echo json_encode($response);
				}
			}else {
				$response["error"] = 1;
				echo json_encode($response);
			}
			// user is already existed - error response
		} else {
			$response["error"] = 2;
			$response["error_msg"] = "User not exist";
			echo json_encode($response);
		}
	}else if ($tag == 'register') {
        // Request type is Register new user
        $fname = $_POST['fname'];
		$lname = $_POST['lname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $subject = "Registration";
        $message = "Hello $fname,\n\nYou have sucessfully registered to our service.\n\nRegards,\nAdmin.";
        $from = "qhubbandroid@gmail.com";
        $headers = "From:" . $from;

        // check if user is already existed
        if ($db->isUserExisted($email)) {
            // user is already existed - error response
            $response["error"] = 2;
            $response["error_msg"] = "User already existed";
            echo json_encode($response);
        }else if(!$db->validEmail($email)){
            $response["error"] = 3;
            $response["error_msg"] = "Invalid Email Id";
            echo json_encode($response);             
		}else {
            // store user
            $user = $db->storeUser($fname, $lname, $email, $password);
            if ($user) {
                // user stored successfully
				$response["success"] = 1;
				$response["user"]["fname"] = $user["firstname"];
				$response["user"]["lname"] = $user["lastname"];
				$response["user"]["email"] = $user["email"];
				$response["user"]["created_at"] = $user["created_at"];
				mail($email,$subject,$message,$headers);
            
                echo json_encode($response);
            } else {
                // user failed to store
                $response["error"] = 1;
                $response["error_msg"] = "JSON Error occurred in Registration";
                echo json_encode($response);
            }
        }
    } else {
         $response["error"] = 3;
         $response["error_msg"] = "JSON ERROR";
        echo json_encode($response);
    }
} else {
    echo "Login API";
}
?>
