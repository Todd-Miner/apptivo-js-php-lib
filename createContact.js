function submitContactForm() {
	//First build the phone, email, and custom attributes objects.  Need to do the same for address if desired.  Create multiple objects and add to array below if submitting multiple.
	let phoneObj = {
		id:'contact_phone_input',//Usually hard-code this and next 2 & last one, can be configured by default works
		phoneType:'Business',
		phoneTypeCode:'PHONE_BUSINESS', 
		phoneNumber:$('#phone').val(), //Form input
		communicationId:1, 
	}
	let customAttributeObj = {
		customAttributeId:'select_2_885',//Hard-coded, easier to use the php function to find attributes by label/value dynamically
		customAttributeName:'select_1583340969520_467_694181583340969520_607', //Hard-coded, easier to use the php function to find attributes by label/value dynamically
		customAttributeValue:'premium',  //Same value seen below
		attributeValues:[],
		customAttributeValueId:'VALUE_1583341428076_640', //ID that pairs with below value
		customAttributeValueId:'select', //This is a hard-coded value from a dropdown menu.  This approach is ok for single values, but it's more flexible to pass in the value and let a common php function find the attribute and value id dynamically
		fieldType:'NUMBER',
	}


	let contactObj = {
		firstName:$('#firstName').val(), //Form input
		lastName:$('#lastName').val(), //Form input
		assigneeObjectRefId:57882, //Matching ID for hard-coded name
		assigneeObjectRefName:'Kenny Clark', //Hard-coded employee name, commonly set to something like API User
		assigneeObjectId:8, //Static id for employees app.  Only change if assigning to a team
		contactStatusName:'Active', //Statuses are unique to each firm if customized.  Default value will work if not in use.
		contactStatusId:10000,
		phoneNumbers: [phoneObj],
		customAttributes: [customAttributeObj]
	}
	$.ajax({ 
		url: 'createContact.php',
		data: {'contactJson':JSON.stringify(contactObj)},
		type: 'post',
		success: function(data) {
			$('#statusMessage').attr( "style", "display: block !important;" );
		}
	});		
}