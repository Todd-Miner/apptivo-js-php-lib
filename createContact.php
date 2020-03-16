<?php
//This is a sample script designed to protect your API/Access key.  It just takes the complete json built in our javascript file, then submits it using the Apptivo phplib wrapper.
//Would typically reccomend adding more processing logic within the php itself to reduce programming effort and increase flexibility.


// *****START CONFIGURATION*****
	//You'll need to go into this file and modify it to change your API keys
	include(dirname(__FILE__)  . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'glocialtech.config.php');
	$configData = getConfig();
	$GLOBALS['debugMode'] = 'print'; //log or print
	//Apptivo API credentials, sample employee provided who we'll make API calls on behalf of
	$api_key = $configData['api_key'];
	$access_key = $configData['access_key'];
	$user_name = $configData['user_name'];
	$logFile = 'createLead.log.txt';
	$GLOBALS['allLogText'] = '';
	$GLOBALS['allLogTextHtml'] = '';
// *****END CONFIGURATION*****
// Initialize the apptivo class.
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'class.apptivo.php');
$apptivoApi = new apptivoApi($api_key, $access_key, $user_name);
//Load common functions
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'commonFunctions.php');


//This php script assumes it's been called by our createContact.js file, and the contact JSON is posted to us
//Just call the API method to save the contact
$newContact = $apptivoApi->save('contacts',$_POST['contactJson']);

print_r($newContact);

?>