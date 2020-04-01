var clientId = drupalSettings.tpedu.tpedusso.clientId;
var callback = drupalSettings.tpedu.tpedusso.callBack;

function tpedussoAuth() {
    window.top.location='https://ldap.tp.edu.tw/oauth/authorize?client_id=' + clientId + '&redirect_uri=' + callback + '&response_type=code&scope=user';
}