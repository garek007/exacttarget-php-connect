<?php 
header("Access-Control-Allow-Origin: *");
//echo '<pre>';
require('00-Includes/exacttarget_soap_client.php');
$wsdl = 'https://webservice.s4.exacttarget.com/etframework.wsdl';
$etusername = 'your_et_username';//Enter the username you use to login to Exact Target
$etpassword = 'your_et_password';//Enter the password you use to login to Exact Target

$subscriber = $_POST["Email_Address"];
$exkey = $_POST["Ex_Key"];

/* 	
Enter your field names below. They should match exactly in Exact Target and in your web form... and here, of course
*/
$fieldNames = array(
	'Email Address',
	'First Name',
	'Last Name',
	'United States Zip Code',
	'State',
	'Country',
	'Birthday',
	'Arts and Culture',
	'Dining and Nightlife',
	'Casinos',
	'Shopping',
	'Spas and Wellness',
	'Sports',
	'Golf',
	'Tours and Sightseeing',
	'Family Travel',
	'Romantic Getaways',
	'LGBT',
	'Signup URL'
);

$mySub = array();
foreach($fieldNames as $field){
	if(isset($_POST[str_replace(' ','_',$field)])){
		$mySub[$field]=trim($_POST[str_replace(' ','_',$field)]);
	}
}

function deleteDERow($subscriber,$client,$externalkey){
		/*% Create ExactTarget_DataExtensionObject*/
		$de = new ExactTarget_DataExtensionObject();
		$de->CustomerKey = $externalkey; //key for the data extension we are updating
		
		/*% ExactTarget_APIProperty */	
		$val1key = new ExactTarget_APIProperty();
		$val1key->Name = "Email Address"; // name of DE field
		$val1key->Value = $subscriber; // value for DE field
		
		$de->Keys[] = $val1key;
		
		$object = new SoapVar($de, SOAP_ENC_OBJECT, 'DataExtensionObject', "http://exacttarget.com/wsdl/partnerAPI");
		
		/* Perform the delete */
		$request = new ExactTarget_DeleteRequest();
		$request->Options = NULL;
		$request->Objects = array($object);
		$results = $client->Delete($request);
		$statuscode = $results->Results->StatusCode;
		//var_dump($results);
		echo "<br><br><br><br>";
	
		if($statuscode == 'OK'){
			echo "Successfully deleted DE row";	
		}else{
			echo "There was an error deleting the row";
			}
}
function findSubscriber($subscriber,$client){
		/* Create the Retrieve request */
        $request = new ExactTarget_RetrieveRequest();
        $request->ObjectType= "DataExtensionObject[Consumer Master List]"; // replace DataExtensionName with the name of the data extension you are retrieving from
		
		// define the data extension fields for the retrieve
		$request->Properties[] = "Email Address"; // data extension field
		$request->Properties[] = "Last Name";    
   
       // $request->Properties[] = "FIRST_NAME";
       // $request->Properties[] = "SITE_GROUP";
		//$request->Properties[] = "item_no"; // data extension field
		
		// Setup a simple filter based on the key column you want to match on
        $sfp= new ExactTarget_SimpleFilterPart();
        $sfp->Value =  array($subscriber);
        $sfp->SimpleOperator = ExactTarget_SimpleOperators::equals;
        $sfp->Property="Email Address";
		
		$request->Filter = new SoapVar($sfp, SOAP_ENC_OBJECT, 'SimpleFilterPart', "http://exacttarget.com/wsdl/partnerAPI");
        $request->Options = NULL;
        $rrm = new ExactTarget_RetrieveRequestMsg();
        $rrm->RetrieveRequest = $request;        
        $results = $client->Retrieve($rrm);  

		$checkit = $results->Results->Properties->Property[0]->Value;
		if($checkit != "" ){


			$rr = new ExactTarget_RetrieveRequest();
			$rr->ObjectType = "Subscriber";  
			
			$rr->Properties =  array();
			$rr->Properties[] = "ID";       
			$rr->Properties[] = "SubscriberKey";
			$rr->Properties[] = "EmailAddress";
			$rr->Properties[] = "Status";
		
			 
			$sfp= new ExactTarget_SimpleFilterPart();
			$sfp->Value =  array($subscriber);
			$sfp->SimpleOperator = ExactTarget_SimpleOperators::equals;
			$sfp->Property="EmailAddress";
		 
			$rr->Filter = new SoapVar($sfp, SOAP_ENC_OBJECT, 'SimpleFilterPart', "http://exacttarget.com/wsdl/partnerAPI");
			$rr->Options = NULL;
			
			
			$rrm = new ExactTarget_RetrieveRequestMsg();
			$rrm->RetrieveRequest = $rr; 					
			$results = $client->Retrieve($rrm);
			
			  
			echo "Subscriber ".$subscriber." was found in the data extension";
				echo "\n";
			echo "This user is ".$results->Results->Status;
		
		
			//var_dump($results);
			//echo $results->OverallStatus;
			
		}else{
			echo "Could not find subscriber";
		}	
	
	
	
}
function addDERow($subscriber,$client,$externalkey){
		/*% ExactTarget_DataExtensionObject */	
		$de = new ExactTarget_DataExtensionObject();
		$de->CustomerKey = $externalkey; //external key/unique identifier for the data extension

		/*% ExactTarget_APIProperty */	
		$val1key = new ExactTarget_APIProperty();
		$val1key->Name = "Email Address"; // name of DE field
		$val1key->Value = $subscriber; // value for DE field

		// add field values to the data extension
		$de->Properties[] = $val1key;
				
		//$object = new SoapVar($de, SOAP_ENC_OBJECT, 'DataExtensionObject', "http://exacttarget.com/wsdl/partnerAPI");
		$object = new SoapVar($de, SOAP_ENC_OBJECT, 'DataExtensionObject', "http://exacttarget.com/wsdl/partnerAPI");
	
		// create the row of the data extension
		$request = new ExactTarget_CreateRequest();
		$request->Options = NULL;
		$request->Objects = array($object);
		$results = $client->Create($request);	
		$statuscode = $results->Results->StatusCode;
		echo "<pre>";
				var_dump($results);
				echo "</pre>";
		/*
		//Ok documenting my code here. Stupid Exact Target doesn't have a consistent object structure
		so I have to first check if an object property exists 'KeyErrors' and if it does, set the errMsg to it, if not, set it to another
		part of the object		*/
		if($statuscode == 'OK'){
			$Msg = "Successfully added Data Extension Row";	
		}else{
			
			if(property_exists($results->Results,'KeyErrors')){
				echo $results->Results->KeyErrors->KeyError->ErrorMessage;
			}else{
				$Msg = $results->Results->ErrorMessage;
				if (strpos($Msg,'duplicate key') !== false) {
					echo "This email address is in the data extension";
				}
					
			}
		}
		//echo "<br><br>".gettype($keyErrs);//gets type of variable that $keyErrs is
}
function addUpdateDE($mySub,$client){

	  //validate email address and make sure they entered a zip code 
	  if (!filter_var($mySub['Email Address'], FILTER_VALIDATE_EMAIL)) {
		echo 'Please provide a valid email address.';return;
	  }   
	  if(empty($mySub['United States Zip Code'])){
		  echo 'Zip code is required.';
		  mailStan("no name", $mySub['Email Address'], "null", "error", "no zipcode entered");
		  return;
	  }
	  if(empty($mySub['Birthday'])){
		  $mySub['Birthday'] = "01/01";
		 // echo $mySub['Birthday'];
	  }
	 if(preg_match("/O/", $mySub['Birthday'])){
		$mySub['Birthday'] = preg_replace("/O/", "0", $mySub['Birthday']);
	 }
	 $ckdate = explode("/", $mySub['Birthday']);
	 /*
	 if($ckdate[0]>12 || $ckdate[0]<1){
		echo 'Please make sure your birthdate is in a valid format MM/DD'; 
		return;
	 }
	 if($ckdate[1]>31 || $ckdate[1]<1){
		echo 'Please make sure your birthdate is in a valid format MM/DD'; 
		return;
	 }	 
	 */
	 if(checkdate($ckdate[0],$ckdate[1],2016)== false){
		echo 'Please make sure your birthdate is in a valid format MM/DD'; 
		return;		 
	 }
	  if(empty($mySub['First Name'])){
		  $mySub['First Name'] = "friends";
		 // echo $mySub['Birthday'];
	  }	  
/* ====================== END ERROR HANDLING SECTION ===================*/

	  $DE = new ExactTarget_DataExtensionObject();
	  $DE->CustomerKey="Cons-10001"; // unique identifier to the data extension
	  
	  $keys = array_keys($mySub);//make a new array with keys from our fields array
	  
	  /*Update can happen only if you have PrimaryKey column in the Data Extension*/ 
	  $apiPropertyKey = new ExactTarget_APIProperty();
	  $apiPropertyKey->Name=$keys[0];  // primary key of the data extension
	  $apiPropertyKey->Value=$mySub[$keys[0]]; // value of the primary key for the row we want to add/update
	  $DE->Keys[] = $apiPropertyKey; // add primary key field to the data exension
	  
	  /*Update other fields*/
	  $i=0;
	  $apiProperty = array();
	  foreach($mySub as $key => $value){
		  $apiProperty[$i] =new ExactTarget_APIProperty();
		  $apiProperty[$i]->Name=$key; // field we want to add/update
		  $apiProperty[$i]->Value=$value; // new value for LastName
		  $i++;
	  }//loop end
	  $DE->Properties=$apiProperty;
	
	  $object1 = new SoapVar($DE, SOAP_ENC_OBJECT, 'DataExtensionObject', "http://exacttarget.com/wsdl/partnerAPI");
	  
	  /*% Create the ExactTarget_SaveOption Object */ 
	  $saveOption = new ExactTarget_SaveOption();                
	  //$saveOption->PropertyName=$DE;
	  $saveOption->PropertyName="DataExtensionObject";
	  $saveOption->SaveAction=ExactTarget_SaveAction::UpdateAdd; // set the SaveAction to add/update

	  // Apply options and object to request and perform update of data extension
	  $updateOptions = new ExactTarget_UpdateOptions();
	  $updateOptions->SaveOptions[] = new SoapVar($saveOption, SOAP_ENC_OBJECT, 'SaveOption', "http://exacttarget.com/wsdl/partnerAPI");
	  $request->Options = new SoapVar($updateOptions, SOAP_ENC_OBJECT, 'UpdateOptions', "http://exacttarget.com/wsdl/partnerAPI");
	  $request = new ExactTarget_CreateRequest();
	  $request->Options = $updateOptions;
	  $request->Objects = array($object1);
	  $results = $client->Update($request);
		 
	  if($err){
		  
	  }
	  $status = $results->OverallStatus;
	  
	  
	  if($status === "OK"){
		  //first sendmail so I can make sure everything went ok
		  //mailStan($mySub['First Name'], $mySub['Email Address'], $results, "success", $mySub['United States Zip Code']);
		  
		  if ($mySub['Signup URL']=="http://www.sandiego.org/newsletter.aspx"){
			  echo "success-redirect";
		  }else if($mySub['Signup URL']=="mobile_site"){
			  echo '<p style="font-size:40px;padding:20px;">Thank you for signing up! We will be in touch soon! <a href="http://m.sandiego.org" >Click here</a> to go back to the site.</p>';  
		  }
		  else{
			  echo "success-redirect";	
		  }
	  }else{
		  $errMsg="Unknown error, please try again in a few minutes.";	  		  
		  mailStan($mySub['First Name'], $mySub['Email Address'], $results, "error", $mySub['United States Zip Code']);
		  echo $errMsg;	
	  }

}
function varDumpToString($var) {
    ob_start();
    var_dump($var);
    $result = ob_get_clean();
    return $result;
}
function mailStan($name, $mail_from, $results, $status, $zip){
	
	$mailToAddress = 'salachniewicz@sandiego.org';
	
	// TURN THIS ON AND MOVE TO BEFORE FIRST FUNCTION FOR TESTING
	//$id = $_GET["id"];
	//$e=$mySub['Email Address'];
	//$z=$mySub['United States Zip Code'];
	//$useragent=$_SERVER['HTTP_USER_AGENT'];
	//$ip = $_SERVER['REMOTE_ADDR'];
	// TURN THIS ON AND MOVE TO BEFORE FIRST FUNCTION FOR TESTING
	
	// For human-readable results
	//ob_start();
	//var_dump($results);
	//$result = ob_get_clean();
	// ...or...
	//$dataStr = print_r($data, TRUE);
	if($results != "null"){
	$resultText = varDumpToString($results);
	}
	//$message="Email Address: ".$e."Zip Code: ".$z." \n ".$useragent." \n ".$ip;
	$message = "Zip Code: ".$zip." \n ";
	$message .= "Email Address: ".$mail_from." \n";
	$message .= $resultText;
	
	if($status == "error"){
		$subject = 'There has been a disturbance in the force.';	
	}else{
		$subject = 'New newsletter signup.';
	}

	// From 
	$header="from: $name <$mail_from>";

	//(to Email, Subject, Message, required headers)
	$send_contact=mail('salachniewicz@sandiego.org',$subject,$message,$header);
	
	// Check, if message sent to your email 
	// display message "We've recived your information"
	//if($send_contact){
	//echo "We've recived your contact information";
	//}
	//else {
	//echo "ERROR";
	//}	
	
	
}
function updateListAttributes($mySub,$client){
	$subscriber = new ExactTarget_Subscriber();
	$subscriber->EmailAddress = $mySub['Email Address'];
	
	$attribute1 = new ExactTarget_Attribute();
	$attribute1->Name = 'Arts and Culture';
	$attribute1->Value = $mySub['Arts and Culture'];
	
	$attribute2 = new ExactTarget_Attribute();
	$attribute2->Name = 'Dining and Nightlife';
	$attribute2->Value = $mySub['Dining and Nightlife'];
	
	$attribute3 = new ExactTarget_Attribute();
	$attribute3->Name = 'Casinos';
	$attribute3->Value = $mySub['Casinos'];
	
	$attribute4 = new ExactTarget_Attribute();
	$attribute4->Name = 'Shopping';
	$attribute4->Value = $mySub['Shopping'];
	
	$attribute5 = new ExactTarget_Attribute();
	$attribute5->Name = 'Spas and Wellness';
	$attribute5->Value = $mySub['Spas and Wellness'];
	
	$attribute6 = new ExactTarget_Attribute();
	$attribute6->Name = 'Sports';
	$attribute6->Value = $mySub['Sports'];
	
	$attribute7 = new ExactTarget_Attribute();
	$attribute7->Name = 'Golf';
	$attribute7->Value = $mySub['Golf'];
	
	$attribute8 = new ExactTarget_Attribute();
	$attribute8->Name = 'Tours and Sightseeing';
	$attribute8->Value = $mySub['Tours and Sightseeing'];							
	
	$attribute9 = new ExactTarget_Attribute();
	$attribute9->Name = 'Family Travel';
	$attribute9->Value = $mySub['Family Travel'];
	
	$attribute10 = new ExactTarget_Attribute();
	$attribute10->Name = 'Romantic Getaways';
	$attribute10->Value = $mySub['Romantic Getaways'];	
	
	$attribute11 = new ExactTarget_Attribute();
	$attribute11->Name = 'LGBT';
	$attribute11->Value = $mySub['LGBT'];	
	
	
	$subscriber->Attributes=array($attribute1,$attribute2,$attribute3,$attribute4,$attribute5,$attribute6,$attribute7,$attribute8,$attribute9,$attribute10,$attribute11);
	
	// Specify the list to add the subscriber to
	/*% ExactTarget_SubscriberList */
	$sublist = new ExactTarget_SubscriberList();
	$sublist->ID = 1298; // specify listID
	$sublist->Action = "update"; // specify what action to apply to subscriber on list (delete, update are other options)
	//use this if you want to also specify list id.
  //  $subscriber->Lists[] = new SoapVar($sublist, SOAP_ENC_OBJECT, 'SubscriberList', "http://exacttarget.com/wsdl/partnerAPI");
     
	 
	$object = new SoapVar($subscriber, SOAP_ENC_OBJECT, 'Subscriber', "http://exacttarget.com/wsdl/partnerAPI");
	
	/*% ExactTarget_CreateRequest */
	$request = new ExactTarget_CreateRequest();
	// Configure Upsert Capabilities for the CreateRequest
	/*% ExactTarget_CreateOptions */
	$requestOptions = new ExactTarget_CreateOptions();
	/*% ExactTarget_SaveOption */
	$saveOption = new ExactTarget_SaveOption();
	$saveOption->PropertyName = "*"; // * All props
	$saveOption->SaveAction = "UpdateAdd"; // Specify upsert save action
	$requestOptions->SaveOptions[] = new SoapVar($saveOption, SOAP_ENC_OBJECT, 'SaveOption', "http://exacttarget.com/wsdl/partnerAPI");
	
	// Apply options and object to request
	$request->Options = new SoapVar($requestOptions, SOAP_ENC_OBJECT, 'CreateOptions', "http://exacttarget.com/wsdl/partnerAPI");
	$request->Objects = array($object);
	
	// Execute the CreateRequest
	$results = $client->Create($request);
	
	//var_dump($results);	 
                       
	
}


try//try to connect
{
	$client = new ExactTargetSoapClient($wsdl, array('trace'=>1));
	/* Set username and password here */
	$client->username = $etusername;
	$client->password = $etpassword;
	
}catch (SoapFault $e) {
	/* output the resulting SoapFault upon an error */
	echo "Error please contact webmaster";
	var_dump($e);
}

//this code is just for our internal private form where we can check to see if people exist in the database and/or delete them.
if (isset($_POST["OptionButtons"])) {
    $choice = $_POST["OptionButtons"];
	
	switch($choice){
		case 'remove': deleteDERow($subscriber,$client,$exkey); break;
		case 'add': addDERow($subscriber,$client,$exkey);break;
		case 'check':findSubscriber($subscriber,$client);break;
		default: break;
	}

}else{
	updateListAttributes($mySub,$client);	
	addUpdateDE($mySub,$client);
	
}
?>
