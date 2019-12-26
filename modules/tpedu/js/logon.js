var clientId = drupalSettings.tpedu.tpedu_sso.client_id;
var callback = drupalSettings.tpedu.tpedu_sso.call_back;
var gclientId = drupalSettings.tpedu.tpedu_sso.google_client_id;
var apiKey = drupalSettings.tpedu.tpedu_sso.google_apikey;
var scopes = 'https://www.googleapis.com/auth/userinfo.email';
var mytoken = '';

function tpedussoAuth() {
    window.top.location='https://ldap.tp.edu.tw/oauth/authorize?client_id=' + clientid + '&redirect_uri=' + callback + '&response_type=code&scope=user';
}

function googleAuth() {
    gapi.auth.authorize({client_id: gclientId, scope: scopes, immediate: true}, handleAuthResult);
}

function handleClientLoad() {
    gapi.client.setApiKey(apiKey);
	window.setTimeout(googleAuth, 1);
	window.setTimeout(refresh_token, 2);
}

function refresh_token() {
	var xmlhttp;
	if (window.XMLHttpRequest) {
		xmlhttp=new XMLHttpRequest();
	} else {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange = function() {
    	if (this.readyState == 4 && this.status == 200) {
      		if (this.responseText) {
	    		window.top.location=this.responseText;
			}
    	}
  	};
  	xmlhttp.open("GET", "/retrieve", true);
	xmlhttp.send();
}

function handleAuthResult(authResult) {
    if (authResult && !authResult.error) {
      var tokenobj=gapi.auth.getToken();
      mytoken=tokenobj.access_token;
	  getMail();
    }
}

function getMail() {
    gapi.client.load('oauth2', 'v2', function() {
        var request = gapi.client.oauth2.userinfo.get();
        request.execute(function(resp) {
	        var user=resp.email;
	        window.top.location = '/retrieve?user=' + user;
        });
    });
}