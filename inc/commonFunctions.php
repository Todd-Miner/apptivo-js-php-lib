<?php
function logIt($logText,$output = false) {
	global $debugMode;
	global $filePath;
	if(!$filePath) {
		$filePath = 'general.log.txt';
	}
	if(!isset($GLOBALS['allLogText'])) {
		$GLOBALS['allLogText'] = '';
		$GLOBALS['allLogTextHtml'] = '';
	}
	$GLOBALS['allLogText'] = $GLOBALS['allLogText'].date('Y-m-d h:i:s').': '.$logText.PHP_EOL;
	$GLOBALS['allLogTextHtml'] = $GLOBALS['allLogTextHtml'].date('Y-m-d h:i:s').': '.$logText.'<br>';
	if($output) {
		$logfile = file_put_contents ($filePath,$GLOBALS['allLogText'],FILE_APPEND);
		$GLOBALS['allLogText'] = '';
	}
	if ($debugMode != 'logOnly' || $output) {
		print $GLOBALS['allLogTextHtml'];
		$GLOBALS['allLogTextHtml'] = '';
	}
}

function array_find($needle, array $haystack)
{
    foreach ($haystack as $key => $value) {
        if (false !== stripos($value, $needle)) {
            return $key;
        }
    }
    return false;
}

function strip($inputStr) {
	//Wrapper for both lcase and trim to prepare strings for comparison
	return trim(strtolower($inputStr));
}

function sStrip($inputStr) {
	//Extension of strip but this completely removes spaces and hyphens in the comparison
	return str_replace(' ','',str_replace('-','',strip($inputStr)));
}


function sComp($inputStr1, $inputStr2) {
	//Generic string compare with trim and lowercase built in.  True for match false for no match.
	if(strip($inputStr1) == strip($inputStr2)) {
		$result = true;
	}else{
		$result = false;
	}
	return $result;
}

function ssComp($inputStr1, $inputStr2) {
	//copy of sComp but this completely removes spaces and hyphens in the comparison
	if(sStrip($inputStr1) == sStrip($inputStr2)) {
		$result = true;
	}else{
		$result = false;
	}
	return $result;
}

function objectToArr($inputObj) {
	//Will take an object and convert it into a single dimensional array using only the values
	$output = [];
	foreach($inputObj as $val) {
		if(is_array($val) || is_object($val)) {
			$output2 = '';
			foreach($val as $val2) {
				if($output2) {
					$output2.= ','.$val2;
				}else{
					$output2 = $val2;
				}
			}
			$output[] = $output2;
		}else{
			$output[] = $val;
		}
	}
	return $output;
}

function getAttrValue($inputLabel,$inputValue,$inputConfig) {
	//For table attributes the inputLabel should be an array: ["Table Section Name","Attribute Name"]
	$webLayout = $inputConfig->webLayout;
	$sectionsNode = json_decode($webLayout);
	$sections = $sectionsNode->sections;
	$foundAttr = false;
		
	foreach($sections as $cSection) {
		$sectionName = $cSection->label;
		$sectionAttributes = $cSection->attributes;
		
		//Proceed if we are checking all attributes, or if if its an array then we only proceed for a table that matches our label
		if( (!is_array($inputLabel)) || (is_array($inputLabel) && sComp($cSection->label,$inputLabel[0])) ) {
			foreach($sectionAttributes as $cAttr) {
				if($cAttr->label) {
					$labelName = $cAttr->label->modifiedLabel;
					$attributeType = $cAttr->type;
					if(!isset($cAttr->attributeTag) || $cAttr->attributeTag == null) {
						$attributeTag = $cAttr->right[0]->tag;
					}else{
						$attributeTag = $cAttr->attributeTag;
					}
					if(!isset($cAttr->tagName) || $cAttr->tagName == null) {
						$attributeTagName = $cAttr->right[0]->tagName;
					}else{
						$attributeTagName = $cAttr->tagName;
					}
					$attributeId = $cAttr->attributeId;
					$selectedValues = [];
					if($attributeType == 'Custom') {
						//This is a potential attribute.  Now let's find the attribute with the matching label.  Both conditions for regular attribute and attribute in table
						if( (!is_array($inputLabel) && sComp($labelName,$inputLabel)) || (is_array($inputLabel) && sComp($labelName,$inputLabel[1])) ) {
							//We have matched the right attribute from settings.  Now match value if it's a dropdown or multi select.
							$matchedAttr = $cAttr;
							if($attributeTag == 'select' || $attributeTag == 'multiSelect' || $attributeTag == 'check') {
								if( ($attributeTag == 'multiSelect' || $attributeTag == 'check') && is_array($inputValue)) {
									$foundVal = false;
									if(isset($cAttr->optionValueList)) {
										$optionList = $cAttr->optionValueList;
									}else{
										$optionList = $cAttr->right[0]->optionValueList;
									}
									foreach($inputValue as $iVal) {
										foreach($optionList as $cVal) {
											//logIt('comparing for field '.$labelName.'  ('.strip($cVal->optionObject).') vs ('.strip($inputValue).')');
											if(sComp($cVal->optionObject,$iVal)) {
												//We have matched the right value for this attribute.  Save them here, and we'll define below.
												$selectedValues[] = $cVal;
												$foundVal = true;
												break;
											}
										}
									}
								}else{
									$foundVal = false;
									if(isset($cAttr->optionValueList)) {
										$optionParent = $cAttr;
										$optionList = $cAttr->optionValueList;
									}else{
										$optionParent = $cAttr->right[0];
										if(isset($cAttr->right[0]->optionValueList)) {
											$optionList = $cAttr->right[0]->optionValueList;
										}else{
											$optionList = $cAttr->right[0]->options;
										}
									}
									foreach($optionList as $cVal) {
										//logIt('comparing for field '.$labelName.'  ('.strip($cVal->optionObject).') vs ('.strip($inputValue).')');
										if(is_object($cVal)) {
											if(sComp($cVal->optionObject,$inputValue)) {
												//We have matched the right value for this attribute.  Save them here, and we'll define below.
												$selectedValue = $cVal->optionObject;
												$selectedValueId = $cVal->optionId; 
												//If the values are the same, return the tagId instead
												if($selectedValue == $selectedValueId) {
													$selectedValueId = $optionParent->tagId;
												}
												$foundVal = true;
												break;
											}
										}else{
											//For single val toggles we just have a text value like yes.  Assume it's single value and assign this value + tagId.
											$selectedValue = 'Yes';
											$selectedValueId = $optionParent->tagId;
											$foundVal = true;
											break;
										}
									}
									if(!$foundVal) {
										//If we end the loop without a value match we must log an exception
										logIt('Could not find a matching value for the attribute label ('.$inputLabel.')  and value ('.$inputValue.')',true);
										$exceptionFile = file_put_contents ('noValueMatch-exceptions.txt','Could not find a matching value for the attribute label ('.$inputLabel.')  and value ('.$inputValue.')'.PHP_EOL,FILE_APPEND);
										return false;
									}
								}
							}
							$matchedAttr = $cAttr;
							$foundAttr = true;
							break;
						}
					}
				}
			}	
		}
		
		if($foundAttr) {
			//Break the 2nd loop once we have attribute
			break;
		}
	}
	if($foundAttr) {
		
		//Now let's build our complete object for this attribute based on type and the matched attribute from settings	
		$newAttr = new stdClass;
		$newAttr->customAttributeType = $attributeTag;
		$newAttr->customAttributeId = $cAttr->attributeId;
		if(isset($cAttr->tagName)) {
			$newAttr->customAttributeName = $cAttr->tagName;
			$newAttr->customAttributeTagName = $cAttr->tagName;
		}else{
			$newAttr->customAttributeName = $cAttr->right[0]->tagName;
			$newAttr->customAttributeTagName = $cAttr->right[0]->tagName;
		}
		switch($attributeTag) {
			case 'multiSelect':
				$newAttr->customAttributeValue = '';
				$newAttr->fieldType = 'NUMBER';
				$newAttr->attributeValues = [];
				//Detect if we set an array of values or a single value
				if(count($selectedValues) > 0) {
					foreach($selectedValues as $cVal) {
						$valueObj = new stdClass;
						$valueObj->attributeId = $cVal->optionId;
						$valueObj->attributeValue =  $cVal->optionObject;
						$newAttr->attributeValues[] = $valueObj;	
						unset($valueObj);
					}
				}else{
					$valueObj = new stdClass;
					$valueObj->attributeId = $selectedValueId;
					$valueObj->attributeValue = $selectedValue;
					$newAttr->attributeValues[] = $valueObj;
				}
			break;
			case 'check':
				$newAttr->customAttributeValue = '';
				$newAttr->fieldType = 'NUMBER';
				$newAttr->attributeValues = [];
				//Detect if we set an array of values or a single value
				if(count($selectedValues) > 0) {
					foreach($selectedValues as $cVal) {
						$valueObj = new stdClass;
						$valueObj->attributeId = $cVal->optionId;
						$valueObj->attributeValue =  $cVal->optionObject;
						$valueObj->shape =  '';
						$valueObj->color =  '';
						$newAttr->attributeValues[] = $valueObj;	
						unset($valueObj);
					}
				}else{
					$valueObj = new stdClass;
					$valueObj->attributeId = $selectedValueId;
					$valueObj->attributeValue = $selectedValue;
					$valueObj->shape =  '';
					$valueObj->color =  '';
					$newAttr->attributeValues[] = $valueObj;
				}
			break;
			case 'currency':
				$newAttr->customAttributeValue = $inputValue;
				$newAttr->currencyCode = 'USD'; //hard-coded for now
				$newAttr->fieldType = 'NUMBER';
			break;
			case 'date':
				$newAttr->customAttributeValue = $inputValue;
				//Assuming inputval is m/d/Y, convert to Y-m-d 
				//$newAttr->dateValue = date('Y-m-d',strtotime($inputValue)).' 00:00:00';
				$newAttr->fieldType = 'NUMBER';
				$newAttr->attributeValues = [];
			break;
			case 'input':
				$newAttr->customAttributeValue = $inputValue;
				$newAttr->fieldType = 'NUMBER';
			break;
			case 'number':
				$newAttr->customAttributeValue = $inputValue;
				$newAttr->numberValue = $inputValue;
				$newAttr->fieldType = 'NUMBER';
			break;
			case 'reference':
				//Reference attributes take an array of objectId, objectRefId, objectRefName
				$newAttr->customAttributeValue = $inputValue[2];
				$newAttr->fieldType = 'NUMBER';
				//If the object id is more than 3 digits then it's a custom app.  We're going to assume this is a cases app extension for now.  Need to refactor later and allow passing in app name and object ID.
				if(strlen($inputValue[0]) > 3) {
					//This is for cases
					$newAttr->caseId = $inputValue[1];
					$newAttr->caseNumber = $inputValue[2];
				}else{
					if($inputValue[0] == '3') {
						//This is for customers, need support for others
						$newAttr->customerId = $inputValue[1];
						$newAttr->customerName = $inputValue[2];
					}elseif($inputValue[0] == '2') {
						//This is for contacts, need support for others
						$newAttr->contactId = $inputValue[1];
						$newAttr->fullName = $inputValue[2];
					}
				}
				$newAttr->attributeValues = [];
				$newAttr->objectId = $inputValue[0];
				$newAttr->objectRefId = $inputValue[1];
				$newAttr->objectRefName = $inputValue[2];
			break;
			case 'referenceField':
				//ReferenceField attributes take an array of objectId, objectRefId, value
				$newAttr->customAttributeValue = $inputValue[2];
				$newAttr->fieldType = $matchedAttr->associatedField->referenceAttributeTag;
				$newAttr->attributeId = $matchedAttr->associatedField->referenceAttributeId;
				$newAttr->objectId = $inputValue[0];
				$newAttr->objectRefId = $inputValue[1];
				$newAttr->refFieldObjectRefName = $inputValue[2];
			break;
			case 'select':
				$newAttr->customAttributeValue = $selectedValue;
				$newAttr->customAttributeValueId = $selectedValueId;
				$newAttr->fieldType = 'NUMBER';
				$newAttr->attributeValues = [];
			break;
			case 'textarea':
				$newAttr->customAttributeValue = $selectedValue;
				$newAttr->attributeValues = [];
			break;
		}
		return $newAttr;
	}
}

?>