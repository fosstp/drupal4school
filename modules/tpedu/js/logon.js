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
		'scope': 'profile',
	}).then(function() {
		var GoogleAuth = gapi.auth2.getAuthInstance();
		var GoogleUser = GoogleAuth.currentUser.get();
		window.top.location = '/retrieve?user=' + GoogleUser.getBasicProfile().getEmail();
	});
}