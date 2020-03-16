<?php
/* ABOUT THIS FILE 
   This is a general class that contains methods commonly used when interacting with the Apptivo API.
*/
class apptivoApi
{
	public $api_key = 'null';
	public $access_key = 'null';
	public $user_name_str = 'null';
	public $ch;
	//Constructor sets the api/access keypair.  Also constructs the curl object so we can start making API requests.  Will destroy curl object on destruct.
	function __construct($input_apikey, $input_accesskey, $user_name) {
		$this->api_key = $input_apikey;
		$this->access_key = $input_accesskey;
		if($user_name) {
			$this->user_name_str = '&userName='.$user_name;
		}
		// Basic curl implementation.  This can be further secured in future.
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER,   0);
		curl_setopt($this->ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	}
	function __destruct()
	{
		curl_close($this->ch);
	}
	function getConfigData($app,$appId = null)
	{
		$objParams = $this->getAppParamters($app);
		//If we provide an app id we can ovverride it here.  This is used for custom apps, so a cases app extension uses app name Cases then the app id.
		if($appId) {
			$appIdNumber = $appId;
		}else{
			$appIdNumber = $objParams['objectId'];
		}
		$api_url = 'https://api.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=getConfigData&objectId='.$appIdNumber.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		return $api_result;
	}
	//Use save for creating new records, and update for changing existing records
	function save($app,$objectData,$extraParams = '',$appId = null) {
		$objParams = $this->getAppParamters($app);
		//For custom apps we need 1 extra param here.  It's returned back by objParams, just need to inject into extraParams
		if($app == 'customapp') {
			$extraParams .= '&customAppObjectId='.$objParams['objectId'];	
		}
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=save'.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		//logIt($api_url.'    with post data: '.http_build_query(array($objParams['objectDataName'] => $objectData)),true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		curl_setopt($this->ch,CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($this->ch,CURLOPT_POSTFIELDS, http_build_query(array($objParams['objectDataName'] => $objectData)));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function update($app, $objectId, $attributeName, $objectData, $extraParams = '',$runMode = 'post') {
		$objParams = $this->getAppParamters($app);
		//For contacts, maybe other apps too, attributeName should be singular
		if($app == 'customers') {
			$aName = '';
		}else{
			$aName = 's';
		}
		if($runMode == 'get') {
			$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=update&objectId='.$objParams['objectId'].'&'.$objParams['objectIdName'].'='.$objectId.'&attributeName'.$aName.'='.urlencode($attributeName).'&'.$objParams['objectDataName'].'='.urlencode($objectData).$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
			logIt($api_url,true,'api.log.txt');
			curl_setopt($this->ch,CURLOPT_URL, $api_url);
			curl_setopt($this->ch,CURLOPT_POST, false);
			curl_setopt($this->ch, CURLOPT_HEADER, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER,   0);
			curl_setopt($this->ch, CURLOPT_SSLVERSION, 6);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}else{
			//default post
			$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=update&objectId='.$objParams['objectId'].'&'.$objParams['objectIdName'].'='.$objectId.'&attributeName'.$aName.'='.urlencode($attributeName) .$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
			logIt($api_url.'    with post data: '.http_build_query(array($objParams['objectDataName'] => $objectData)),true,'api.log.txt');
			curl_setopt($this->ch,CURLOPT_URL, $api_url);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($this->ch,CURLOPT_POSTFIELDS, http_build_query(array($objParams['objectDataName'] => $objectData)));
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->ch, CURLOPT_URL, $api_url);
		}
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		logIt(curl_error($this->ch));
		return $api_response;
	}
	function deleteRecord($app, $objectId) {
		$objParams = $this->getAppParamters($app);
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=delete&'.$objParams['objectIdName'].'='.$objectId.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getById($app,$objectId) {
		$objParams = $this->getAppParamters($app);
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=getById&'.$objParams['objectIdName'].'='.$objectId.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getAllBySearchText($app,$searchText,$extraParams = '') {
		$objParams = $this->getAppParamters($app);
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=getAllBySearchText&searchText='.urlencode($searchText).$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);
		return $api_response;
	}
	function getAllByAdvancedSearch($app,$searchData,$extraParams = '') {
		$objParams = $this->getAppParamters($app);
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=getAllByAdvancedSearch&searchData='.$searchData.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getCustomerContacts($customerId,$extraParams = '') {
		$api_url = 'https://www.apptivo.com/app/dao/v6/customers?a=getCustomerContacts&customerId='.$customerId.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function associateContactToCustomer($customerId,$contactIds,$extraParams = '') {
		$api_url = 'https://www.apptivo.com/app/dao/v6/customers?a=addContact&customerId='.$customerId.'&contactIds='.$contactIds.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getActivitiesByAdvancedSearch($activityType,$searchData,$isFromApp = 'home',$startIndex = 0,$numRecords = 50,$extraParams = '') {
		$api_url = 'https://www.apptivo.com/app/dao/activities?a=getActivitiesByAdvancedSearch&activityType='.$activityType.'&searchData='.$searchData.'&isFromApp='.$isFromApp.'&startIndex='.$startIndex.'&numRecords='.$numRecords.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function createActivity($activityType,$activityData, $actType = 'home', $extraParams = '') {
		//Set the standard parameters to be used in the URL below
		switch($activityType) {
			case 'Follow Up':
				$methodName = 'createFollowUpActivity';
				$dataStr = '&followUpData='.$activityData;
			break;
			case 'Event':
				$methodName = 'createEvent';
				$dataStr = '&eventData='.$activityData;
			break;
			case 'Task':
				$methodName = 'createTask';
				$dataStr = '&taskData='.$activityData;
			break;
		}
		$api_url = 'https://www.apptivo.com/app/dao/activities?a='.$methodName.$dataStr.'&actType='.$actType.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function updateActivity($activityData, $actType = 'home', $extraParams = '') {
		$activityType = $activityData->activityType;
		$sourceObjectId = $activityData->objectId;
		$activityId = $activityData->id;
		//Set the standard parameters to be used in the URL below
		switch($activityType) {
			case 'Follow Up':
				$methodName = 'updateFollowUpActivity';
				$dataStr = 'followUpData';
			break;
			case 'Event':
				$methodName = 'updateEvent';
				$dataStr = 'eventData';
			break;
			case 'Task':
				$methodName = 'updateTask';
				$dataStr = 'taskData';
			break;
		}
		$api_url = 'https://www.apptivo.com/app/dao/activities?a='.$methodName.'&activityId='.$activityId.'&taskData={}&actType='.$actType.'&sourceObjectId='.$sourceObjectId.$extraParams.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url.'    with post data: '.http_build_query(array('activityData' => json_encode($activityData))),true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		curl_setopt($this->ch,CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($this->ch,CURLOPT_POSTFIELDS, http_build_query(array('activityData' => json_encode($activityData))));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function deleteActivity($activityId,$objectId) {
		$api_url = 'https://www.apptivo.com/app/dao/activities?a=deleteActivity&activityId='.$activityId.'&objectId='.$objectId.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function createWorkLog($workLogData) {
		$api_url = 'https://www.apptivo.com/app/dao/activities?a=createWorkLog&workLogData='.$workLogData.'&workLogDetails='.$workLogData.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function startWorkLog($workLogData) {
		$api_url = 'https://www.apptivo.com/app/dao/v6/cases?a=startWorkLog&workLogData='.$workLogData.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function stopWorkLogByWorkLogId($caseId,$workLogId,$endDateTime) {
		$api_url = 'https://www.apptivo.com/app/dao/v6/cases?a=stopWorkLogByWorkLogId&caseId='.$caseId.'&workLogId='.$workLogId.'&endDateTime='.$endDateTime.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getAllByCustomView($app,$viewCode,$startIndex=0,$numRecords=0,$extraParams = '') {
		$objParams = $this->getAppParamters($app);
		$api_url = 'https://api.apptivo.com/app/dao/v5/appsettings?a=getAllByCustomView&objectId='.$objParams['objectId'].'&startIndex='.$startIndex.'&numRecords='.$numRecords.$extraParams.'&viewCode='.$viewCode.'&status=0&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,false,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function createNote($noteDetails) {
		$api_url = 'https://api.apptivo.com/app/dao/note?a=createNote&noteDetails='.json_encode($noteDetails).'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function sendEmail($emailData,$objectId,$objectRefId,$isFromApp = 'App',$closeObject = 'false') {
		$api_url = 'https://api.apptivo.com/app/dao/emails?a=send&emailData='.$emailData.'&objectId='.$objectId.'&objectRefId='.$objectRefId.'&isFromApp='.$isFromApp.'&closeObject='.$closeObject.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key.$this->user_name_str;
		logIt($api_url,true,'api.log.txt');
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getIdFromLeadSourceName($leadSourceName) {
		$leadsConfig = json_decode($this->getConfigData('leads'));
		foreach($leadsConfig->leadSources as $curSource) {
			if(strtolower($leadSourceName) == strtolower($curSource->name)) {
				$leadSourceId = $curSource->id;
			}	
		}
		return $leadSourceId;
	}
	function getPropertyAvailability($propertyId) {
		$objParams = $this->getAppParamters('properties');
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=getPropertyAvailability&propertyId='.$propertyId.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function setAvailStartDate($propertyId,$availabilityStartDay,$years,$isUpdateStartDay = true) {
		$objParams = $this->getAppParamters('properties');
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=createOrUpdatePropertyAvailability&propertyId='.$propertyId.'&availabilityStartDay='.$availabilityStartDay.'&availabilityStartDayCode='.$this->getStartDayCode($availabilityStartDay).'&years='.$years.'&isUpdateStartDay='.$isUpdateStartDay.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function updatePropertyWeek($propertyId,$availabilityYear,$weekIndex,$isAvailable = 'Y',$bookedPrice,$amount) {
		$objParams = $this->getAppParamters('properties');
		$api_url = 'https://www.apptivo.com/app/dao/v6/'.$objParams['objectUrlName'].'?a=updatePropertyWeek&propertyId='.$propertyId.'&availabilityYear='.$availabilityYear.'&weekIndex='.$weekIndex.'&isAvailable='.$isAvailable.'&bookedPrice='.$bookedPrice.'&amount='.$amount.'&apiKey='.$this->api_key.'&accessKey='.$this->access_key;
		$logfile = file_put_contents ('api.log.txt',date('Y-m-d h:i:s').': '.$api_url.PHP_EOL,FILE_APPEND);
		curl_setopt($this->ch,CURLOPT_URL, $api_url);
		$api_result = curl_exec($this->ch);
		$api_response = json_decode($api_result);	
		return $api_response;
	}
	function getStartDayCode($startDay) {
		switch(strtolower($startDay)) {
			case 'sunday':
				return 1;
			break;
			case 'monday':
				return 2;
			break;
			case 'tuesday':
				return 3;
			break;
			case 'wednesday':
				return 4;
			break;
			case 'thursday':
				return 5;
			break;
			case 'friday':
				return 6;
			break;
			case 'saturday':
				return 7;
			break;
		}
	}
	
	function getAppParamters($app) {
		//Set the standard parameters to be used in the URL below
		switch(strtolower($app)) {
			case 'cases':
				$objParams = Array(
					'objectUrlName' => 'cases',
					'objectDataName' => 'caseData',
					'objectIdName' => 'caseId',
					'objectId' => 59
				);
			break;
			case 'contacts':
				$objParams = Array(
					'objectUrlName' => 'contacts',
					'objectDataName' => 'contactData',
					'objectIdName' => 'contactId',
					'objectId' => 2
				);
			break;
			case 'customapp':
				$objParams = Array(
					'objectUrlName' => 'customapp',
					'objectDataName' => 'customAppData',
					'objectIdName' => 'customAppId',
					'objectId' => 2202688
				);
			break;
			case 'customers':
				$objParams = Array(
					'objectUrlName' => 'customers',
					'objectDataName' => 'customerData',
					'objectIdName' => 'customerId',
					'objectId' => 3
				);
			break;
			case 'employees':
				$objParams = Array(
					'objectUrlName' => 'employees',
					'objectDataName' => 'employeeData',
					'objectIdName' => 'employeeId',
					'objectId' => 8
				);
			break;
			case 'estimates':
				$objParams = Array(
					'objectUrlName' => 'estimates',
					'objectDataName' => 'estimateData',
					'objectIdName' => 'estimateId',
					'objectId' => 155
				);
			break;
			case 'leads':
				$objParams = Array(
					'objectUrlName' => 'leads',
					'objectDataName' => 'leadData',
					'objectIdName' => 'leadId',
					'objectId' => 4
				);
			break;
			case 'opportunities':
				$objParams = Array(
					'objectUrlName' => 'opportunities',
					'objectDataName' => 'opportunityData',
					'objectIdName' => 'opportunityId',
					'objectId' => 11
				);
			break;
			case 'orders':
				$objParams = Array(
					'objectUrlName' => 'orders',
					'objectDataName' => 'orderData',
					'objectIdName' => 'orderId',
					'objectId' => 12
				);
			break;
			case 'projects':
				$objParams = Array(
					'objectUrlName' => 'projects',
					'objectDataName' => 'projectInformation',
					'objectIdName' => 'projectId',
					'objectId' => 88
				);
			break;
			case 'properties':
				$objParams = Array(
					'objectUrlName' => 'properties',
					'objectDataName' => 'propertyData',
					'objectIdName' => 'propertyId',
					'objectId' => 160
				);
			break;
			case 'suppliers':
				$objParams = Array(
					'objectUrlName' => 'suppliers',
					'objectDataName' => 'supplierData',
					'objectIdName' => 'supplierId',
					'objectId' => 37
				);
			break;
			default:
				//For custom apps we should pass in customapp-appid
				if(strpos($app,'customapp-') !== false) {
					$appParts = explode('-',$app);
					$objParams = Array(
						'objectUrlName' => 'customapp',
						'objectDataName' => 'customAppData',
						'objectIdName' => 'customAppId',
						'objectId' => $appParts[1]
					);
				}
		}
		return $objParams;
	}
}


function updateOrAddCustomAttributeNew($inputObj,$customAttrObj, $mode = 1) {
	//We loop through the current attributes to see if the new one exists, and update if found, or insert if not found
	//Mode is either 1 or 2.
	//Mode 1 indicates that we want to replace any value that is not exactly the same. 
	//Mode 2 indicates that we only want to update the value if nothing previously exists
	//The resultCodes are 1 or 2.
	//Result 1 means we should update the attribute
	//Result 2 means we should not update the attribute
	logIt('Starting function updateorAddCustomAttribute to check for updates on attributeId='.$customAttrObj->customAttributeId);
	$resultCode = 0;
	$count = 0;
	foreach($inputObj->customAttributes as $curAttribute) {	
		if($curAttribute->customAttributeId == $customAttrObj->customAttributeId) { 
			logIt('customAttributeId='.$curAttribute->customAttributeId.'   and    customAttributeValue='.$curAttribute->customAttributeValue,true);
			if($mode == 2 && strlen($curAttribute->customAttributeValue) > 1) {
				$resultCode = 2;
			}elseif($mode == 2 && strlen($curAttribute->customAttributeValue) < 3) {
				$resultCode = 1;
			}elseif($curAttribute->customAttributeValue == $customAttrObj->customAttributeValue || urlencode($curAttribute->customAttributeValue) == $customAttrObj->customAttributeValue) {
				$resultCode = 2;
				logIt('existing value matches the new value, resultCode 2',true);
			}else{
				$resultCode = 1;
				logIt('No matching exceptions, this is a new value.  resultCode 1',true);
			}
			if($resultCode == 1 || $resultcode == 2) {
				logIt('resultCode is 1, setting attribute values',true);
				switch (strtolower($customAttrObj->customAttributeType)) {
					case 'select':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						$inputObj->customAttributes[$count]->customAttributeValueId = $customAttrObj->customAttributeValueId;
					break;
					case 'date':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						if($inputObj->customAttributes[$count]->customAttributeValueId) {
							unset($inputObj->customAttributes[$count]->customAttributeValueId);
						}
					break;
					case 'number':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						$inputObj->customAttributes[$count]->numberValue = $customAttrObj->customAttributeValue;
					break;
					case 'currency':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						$inputObj->customAttributes[$count]->numberValue = $customAttrObj->customAttributeValue;
						$inputObj->customAttributes[$count]->currencyCode = $customAttrObj->currencyCode;
					break;
					case 'reference':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						if($customAttrObj->employeeId) {
							$inputObj->customAttributes[$count]->employeeId = $customAttrObj->employeeId;
							$inputObj->customAttributes[$count]->employeeName = $customAttrObj->employeeName;
						}
					break;
					case 'referencefield':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						//This field type has a lot of potential values we need to handle
						if($customAttrObj->objectId) {
							$inputObj->customAttributes[$count]->objectId = $customAttrObj->objectId;
							$inputObj->customAttributes[$count]->objectRefId = $customAttrObj->objectRefId;
						}
						if($customAttrObj->objectRefName) {
							$inputObj->customAttributes[$count]->objectRefName = $customAttrObj->objectRefName;
						}
						if($customAttrObj->employeeId) {
							$inputObj->customAttributes[$count]->employeeId = $customAttrObj->employeeId;
							$inputObj->customAttributes[$count]->employeeName = $customAttrObj->employeeName;
						}
						if($customAttrObj->contactId) {
							$inputObj->customAttributes[$count]->contactId = $customAttrObj->contactId;
							$inputObj->customAttributes[$count]->fullName = $customAttrObj->fullName;
						}
						if($customAttrObj->customAttributeValue1) {
							$inputObj->customAttributes[$count]->customAttributeValue1 = $customAttrObj->customAttributeValue1;
						}
						if($customAttrObj->customAttributeValueId) {
							$inputObj->customAttributes[$count]->customAttributeValueId = $customAttrObj->customAttributeValueId;
						}
						if($customAttrObj->customAttributeValue) {
							$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
						}
						if($customAttrObj->customAttributeTagName) {
							$inputObj->customAttributes[$count]->customAttributeTagName = $customAttrObj->customAttributeTagName;
						}
						if($customAttrObj[$customAttrObj->customAttributeTagName]) {
							$inputObj->customAttributes[$count]->$customAttrObj->customAttributeTagName = $customAttrObj[$customAttrObj->customAttributeTagName];
						}
						if($customAttrObj->fieldType) {
							$inputObj->customAttributes[$count]->fieldType = $customAttrObj->fieldType;
						}
					case 'input':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttrObj->customAttributeValue;
					break;
					default:
						logIt('ERROR: function updateOrAddCustomAttribute found an attribute type that was not supported.',true);
						die();
				}
			}
		}
		$count = $count + 1;
	}
	if($resultCode == 0) {
		array_push($inputObj->customAttributes,$customAttrObj);
		$resultCode = 1;
	}
	$response = new stdClass;
	$response->returnObj = $inputObj;
	$response->resultCode = $resultCode;
	return $response;
} 

//This version of the function is deprecated.  It uses arrays instead of objects, causes issues.  Leaving for backwards compatibility.
function updateOrAddCustomAttribute($inputObj,$customAttributeArray, $mode = 1) {
	//We loop through the current attributes to see if the new one exists, and update if found, or insert if not found
	//Mode is either 1 or 2.
	//Mode 1 indicates that we want to replace any value that is not exactly the same. 
	//Mode 2 indicates that we only want to update the value if nothing previously exists
	//The resultCodes are 1 or 2.
	//Result 1 means we should update the attribute
	//Result 2 means we should not update the attribute
	logIt('Starting function updateorAddCustomAttribute to check for updates on attributeId='.$customAttributeArray['customAttributeId']);
	$resultCode = 0;
	$count = 0;
	foreach($inputObj->customAttributes as $curAttribute) {	
		if($curAttribute->customAttributeId == $customAttributeArray['customAttributeId']) { 
			logIt('customAttributeId='.$curAttribute->customAttributeId.'   and    customAttributeValue='.$curAttribute->customAttributeValue,true);
			if($mode == 2 && strlen($curAttribute->customAttributeValue) > 1) {
				$resultCode = 2;
			}elseif($mode == 2 && strlen($curAttribute->customAttributeValue) < 3) {
				$resultCode = 1;
			}elseif($curAttribute->customAttributeValue == $customAttributeArray['customAttributeValue'] || urlencode($curAttribute->customAttributeValue) == $customAttributeArray['customAttributeValue']) {
				$resultCode = 2;
				logIt('existing value matches the new value, resultCode 2',true);
			}else{
				$resultCode = 1;
				logIt('No matching exceptions, this is a new value.  resultCode 1',true);
			}
			if($resultCode == 1 || $resultcode == 2) {
				logIt('resultCode is 1, setting attribute values',true);
				switch (strtolower($customAttributeArray['customAttributeType'])) {
					case 'select':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						$inputObj->customAttributes[$count]->customAttributeValueId = $customAttributeArray['customAttributeValueId'];
					break;
					case 'date':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						if($inputObj->customAttributes[$count]->customAttributeValueId) {
							unset($inputObj->customAttributes[$count]->customAttributeValueId);
						}
					break;
					case 'number':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						$inputObj->customAttributes[$count]->numberValue = $customAttributeArray['customAttributeValue'];
					break;
					case 'currency':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						$inputObj->customAttributes[$count]->numberValue = $customAttributeArray['customAttributeValue'];
						$inputObj->customAttributes[$count]->currencyCode = $customAttributeArray['currencyCode'];
					break;
					case 'reference':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						if($customAttributeArray['employeeId']) {
							$inputObj->customAttributes[$count]->employeeId = $customAttributeArray['employeeId'];
							$inputObj->customAttributes[$count]->employeeName = $customAttributeArray['employeeName'];
						}
					break;
					case 'referencefield':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						//This field type has a lot of potential values we need to handle
						if($customAttributeArray['objectId']) {
							$inputObj->customAttributes[$count]->objectId = $customAttributeArray['objectId'];
							$inputObj->customAttributes[$count]->objectRefId = $customAttributeArray['objectRefId'];
						}
						if($customAttributeArray['objectRefName']) {
							$inputObj->customAttributes[$count]->objectRefName = $customAttributeArray['objectRefName'];
						}
						if($customAttributeArray['employeeId']) {
							$inputObj->customAttributes[$count]->employeeId = $customAttributeArray['employeeId'];
							$inputObj->customAttributes[$count]->employeeName = $customAttributeArray['employeeName'];
						}
						if($customAttributeArray['contactId']) {
							$inputObj->customAttributes[$count]->contactId = $customAttributeArray['contactId'];
							$inputObj->customAttributes[$count]->fullName = $customAttributeArray['fullName'];
						}
						if($customAttributeArray['customAttributeValue1']) {
							$inputObj->customAttributes[$count]->customAttributeValue1 = $customAttributeArray['customAttributeValue1'];
						}
						if($customAttributeArray['customAttributeValueId']) {
							$inputObj->customAttributes[$count]->customAttributeValueId = $customAttributeArray['customAttributeValueId'];
						}
						if($customAttributeArray['customAttributeValue']) {
							$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
						}
						if($customAttributeArray['customAttributeTagName']) {
							$inputObj->customAttributes[$count]->customAttributeTagName = $customAttributeArray['customAttributeTagName'];
						}
						if($customAttributeArray[$customAttributeArray['customAttributeTagName']]) {
							$inputObj->customAttributes[$count]->$customAttributeArray['customAttributeTagName'] = $customAttributeArray[$customAttributeArray['customAttributeTagName']];
						}
						if($customAttributeArray['fieldType']) {
							$inputObj->customAttributes[$count]->fieldType = $customAttributeArray['fieldType'];
						}
					case 'input':
						$inputObj->customAttributes[$count]->customAttributeValue = $customAttributeArray['customAttributeValue'];
					break;
					default:
						logIt('ERROR: function updateOrAddCustomAttribute found an attribute type that was not supported.',true);
						die();
				}
			}
		}
		$count = $count + 1;
	}
	if($resultCode == 0) {
		array_push($inputObj->customAttributes,$customAttributeArray);
		$resultCode = 1;
	}
	return array(
		'returnObj' => $inputObj,
		'resultCode' => $resultCode
	);
} 
?>