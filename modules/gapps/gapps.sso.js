var anonymous = Drupal.settings.gapps.anonymous;
var teacherDomain = Drupal.settings.gapps.teacherDomain;
var studentDomain = Drupal.settings.gapps.studentDomain;
var clientId = Drupal.settings.gapps.clientId;
var apiKey = Drupal.settings.gapps.apiKey;
var scopes = Drupal.settings.gapps.scopes;
var mytoken = '';

function handleClientLoad() {
  gapi.client.setApiKey(apiKey);
  if (anonymous == '1') {
    window.setTimeout(checkAuth,1);
  }
  else {
    gapi.auth.signOut();
  }
}

function checkAuth() {
    gapi.auth.authorize({client_id: clientId, scope: scopes, immediate: true}, handleAuthResult);
}

function AuthClick() {
    gapi.auth.authorize({client_id: clientId, scope: scopes, immediate: false}, handleAuthResult);
}

function handleAuthResult(authResult) {
  if (authResult && !authResult.error) {
    var tokenobj=gapi.auth.getToken();
//	alert(JSON.stringify(tokenobj));
    var mytoken=tokenobj.access_token;
    gapi.client.load('oauth2', 'v2', function() {
      var request = gapi.client.oauth2.userinfo.get();
      request.execute(function(resp) {
	    var found=0;
        var myuser=resp.email.split("@")[0];
        var domain=resp.email.split("@")[1];
        if (teacherDomain != '' && domain == teacherDomain) {
          found=1;
          window.location.href = Drupal.settings.basePath + 'gapps/google_login/' + myuser + '/' + mytoken;
        } else if (studentDomain != '' && domain == studentDomain) {
          found=2;
          window.location.href = Drupal.settings.basePath + 'gapps/google_login/' + myuser + '/' + mytoken;
        }
	    if (found==0) {
          var message = 'Please use correct Google Apps domain account to login, input ';
          var message_utf8 = '請使用正確的網域帳號登入，在使用者名稱後面加上 ';
          if (teacherDomain != '' && studentDomain != '') {
            message = message + '@' + teacherDomain + ' or @' + studentDomain + ' after your username.';
            message_utf8 = message_utf8 + '@' + teacherDomain + '或@' + studentDomain + '！';
		  } else if (teacherDomain != '') {
            message = message + '@' + teacherDomain + ' after your username.';
            message_utf8 = domains_utf8 + '@' + teacherDomain + '！';
		  } else if (studentDomain != '') {
            message = message + '@' + studentDomain + ' after your username.';
            message_utf8 = domains_utf8 + '@' + studentDomain + '！';
		  }
          alert(message_utf8 + '\n\n' + message);
        }
      });
    });
  }
}
