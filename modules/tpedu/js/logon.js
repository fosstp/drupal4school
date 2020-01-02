var clientId = drupalSettings.tpedu.tpedusso.clientId;
var callback = drupalSettings.tpedu.tpedusso.callBack;
var gclientId = drupalSettings.tpedu.tpedusso.googleClientId;
var apiKey = drupalSettings.tpedu.tpedusso.googleApikey;

function tpedussoAuth() {
    window.top.location='https://ldap.tp.edu.tw/oauth/authorize?client_id=' + clientId + '&redirect_uri=' + callback + '&response_type=code&scope=user';
}

function googleAuth() {
	gapi.load('client', start);
}

function start() {
	gapi.client.init({
		'apiKey': apiKey,
		'clientId': gclientId,
		'scope': 'email',
	}).then(function() {
		return gapi.client.request({
			//see https://accounts.google.com/.well-known/openid-configuration
			'path': 'https://openidconnect.googleapis.com/v1/userinfo',
		});
	}).then(function(response) {
			window.top.location = '/retrieve?user=' + response.result.email;
		}, function(reason) {
			console.log('Error: ' + reason.result.error.message);
	});
}