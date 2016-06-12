<?php

/**
 * @file
 * Contains \Drupal\simsauth\Form\simsauthConfigForm.
 */

namespace Drupal\simsauth\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;

class simsauthConfigForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simsauth_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('SIMS Authentication Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
  }

   /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simsauth.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	// Default settings
    $config = $this->config('simsauth.settings');

    $error = [];
    if (!extension_loaded('ibm_db2')) {
      $error[] = $this->t('IBM DB2 client driver(V9.5 above) @link', array( "@link" => Link::fromTextAndUrl($this->t('if you use linux x64, you can download it here!'), Url::fromUri('https://www-304.ibm.com/support/docview.wss?rs=71&uid=swg27007053'))));
      $error[] = $this->t('ibm-db2 module for php(PEAR package)');
    }
    if (!extension_loaded('ldap')) {
      $error[] = $this->t('php-ldap or php5-ldap module');
    }
    if (count($error) > 0) {
      drupal_set_message($this->t('Before enable this drupal project, you must install:<ul>@error</ul>', array('@error' => '<li>' . implode('</li><li>', $error) . '</li>')), 'warning');
	  $form['setup_helper'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('How to install ibm_db2 module:<ol><li>Disable selinux:<br>vi /etc/selinux/config<br>SELINUX=disabled</li><li>Install packages:<br>yum install gcc ksh zip php-pear</li><li>Make directory:<br>mkdir /opt/ibm</li><li>Decompress dsdriver at /opt/ibm/:<br>tar -xvf the-package-download-from-ibm-com_dsdriver.tar.gz</li><li>Change permission for the instalation script in /opt/ibm/dsdriver:<br>chmod 755 installDSDriver</li><li>Run the installation script:<br>cd /opt/ibm/dsdriver<br>./installDSDriver</li><li>Download and install the driver using the pecl:<br>pecl install ibm_db2</li><li>Configure the installation directory:<br>DB2 Installation Directory? : /opt/ibm/dsdriver</li><li>Change php.ini add one line:<br>extension = ibm_db2.so</li><li>Reboot the Apache:<br>service httpd restart</li></ol>'),
	  );
    }
    $form['simsauth_ldap'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication Server Setup'),
      '#collapsible' => FALSE,
    );
    $form['simsauth_ldap']['ldap_server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authentication Server'),
      '#default_value' => $config->get('simsauth.ldap_server'),
      '#description' => $this->t('Your LDAP server DNS record or NAT IP. Usually same as IBM DB2 Server.'),
      '#required' => TRUE,
    );
    $form['simsauth_ldap']['ldap_port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('LDAP Connect Port'),
      '#default_value' => $config->get('simsauth.ldap_port'),
      '#description' => $this->t('Which port is the LDAP server query port?'),
      '#required' => TRUE,
    );
    $form['simsauth_ldap']['ldap_basedn'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('LDAP Base DN'),
      '#default_value' => $config->get('simsauth.ldap_basedn'),
      '#description' => $this->t('Please input the Base DN that containing user entries.'),
      '#required' => TRUE,
    );
    $form['simsauth_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User Info and Syncing'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['simsauth_user']['message'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('<p>In IBM DB2 database, the students have no department(table name: SCHDEPT) and job title(table name: SCHPOSITION), so this module will use [Student] as their department, and use their current attending class name as their job title. When you enable role sync function, the department come from DB2 SCHDEPT table will sync to drupal roles immediate, no matter has account in there or not. When user log in they will not automatic assign to the roles, unless you enable [evaluate user roles when they login] function before that. The automatic evaluate function will skip the administer role, so if you assigned someone to be an administer manually, they will always be an administer, until you revoke them manually.</p>'),
    );
    $form['simsauth_user']['display_depname'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display department name in front of the user`s realname.'),
      '#default_value' => $config->get('simsauth.display_depname'),
      '#description' => $this->t('Check this box if you want to display department name of user in front of the user`s realname.'),
    );
    $form['simsauth_user']['display_title'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display job title in front of the user`s realname.'),
      '#default_value' => $config->get('simsauth.display_title'),
      '#description' => $this->t('Check this box if you want to display job title of user in front of the user`s realname but after the department name.'),
    );
    $form['simsauth_user']['role_sync'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatic role population from SIMS database'),
      '#default_value' => $config->get('simsauth.role_sync'),
      '#description' => $this->t('Automatically created role basis the school department.'),
    );
    $form['simsauth_user']['role_evaleverytime'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Evaluate user roles when they login.'),
      '#default_value' => $config->get('simsauth.role_evaleverytime'),
      '#description' => $this->t('NOTE: This means users could loose any roles (except administer role) that have been assigned manually in Drupal.'),
    );
    $form['simsauth_user']['sso_ldap_uid'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Sync Account Name to LDAP Server.'),
      '#default_value' => $config->get('simsauth.sso_ldap_uid'),
      '#description' => $this->t('Check this box if you want to let people set account name back to IBM DB2 database and LDAP Server. Disabling this to prevent change name from the user profile form.'),
    );
    $form['simsauth_user']['sso_ldap_pwd'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Sync Password to LDAP Server.'),
      '#default_value' => $config->get('simsauth.sso_ldap_pwd'),
      '#description' => $this->t('Check this box if you want to let people set passwords back to LDAP Server. Disabling this to prevent change password from the user profile form.'),
    );
    $form['simsauth_std'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Account Setting for Students'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['simsauth_std']['message'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('<p>In IBM DB2 database, the students have not their own account record, so this module will use the STDNO as their account, and use the last 6 digitals of their security ID as their password. But You can change them below anyway. The email address take from STUDENT table there has a field named MAIL. If your school provided another email service to students, please input the mail address prefix and suffix below, or just leave them blank to use the data come from MAIL field.</p>'),
    );
    $form['simsauth_std']['student_account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Students Default Account Name'),
      '#default_value' => $config->get('simsauth.student_account'),
      '#description' => $this->t('The SQL statement of data field to use as account name. Usually input the field name like STDNO, If you want combind multiple field use ||(double pipe) to concat strings, Example: YEAR||CLASSNO||SEAT.'),
      '#required' => TRUE,
    );
    $form['simsauth_std']['student_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Students Default Password'),
      '#default_value' => $config->get('simsauth.student_password'),
      '#description' => $this->t('The SQL statement of data field to use as password. Usually input the field name like STDNO, If you want combind multiple field use ||(double pipe) to concat strings, Example: YEAR||CLASSNO||SEAT.'),
      '#required' => TRUE,
    );
    $form['simsauth_std']['student_custom_password'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow students to custom their own password'),
      '#default_value' => $config->get('simsauth.student_custom_password'),
    );
    $form['simsauth_std']['student_mail_account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Student mail address prefix'),
      '#default_value' => $config->get('simsauth.student_mail_account'),
      '#description' => $this->t('The SQL statement of data field to use as email address part before @.  Usually input the field name STDNO, If you want combind multiple field use ||(double pipe) to concat strings, Example: YEAR||CLASSNO||SEAT.'),
    );
    $form['simsauth_std']['student_mail_address'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Student mail address suffix'),
      '#default_value' => $config->get('simsauth.student_mail_address'),
      '#description' => $this->t('The student email address part after @. If your school have using Google Apps service, you should input the Google Apps domain name.'),
    );
    $form['simsauth_auth'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal Authentication'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['simsauth_auth']['loginname_desc'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login Form Name Description'),
      '#default_value' => $config->get('simsauth.loginname_desc'),
      '#description' => $this->t('Input the message to let user know they should login with their custom account name of School Information Management System.'),
    );
    $form['simsauth_auth']['password_desc'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login Form Password Description'),
      '#default_value' => $config->get('simsauth.password_desc'),
      '#description' => $this->t('Input the message to let user know they should login with their School Information Management System password.'),
    );
    $form['simsauth_auth']['allowdefaultlogin'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow authentication with local Drupal accounts'),
      '#default_value' => $config->get('simsauth.allowdefaultlogin'),
      '#description' => $this->t('Check this box if you want to let people log in with local Drupal accounts (without using simsauth module).'),
    );
    $form['simsauth_auth']['logingotourl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Optionally, specify a URL for users to go to after logging in'),
      '#default_value' => $config->get('simsauth.logingotourl'),
      '#description' => $this->t('Example:') . 'node/news',
    );
    $form['simsauth_auth']['logoutgotourl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Optionally, specify a URL for users to go to after logging out'),
      '#default_value' => $config->get('simsauth.logoutgotourl'),
      '#description' => $this->t('Example:') . 'node/news',
    );
    $form['simsauth_auth']['personal_data_notice'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show personal data notice link'),
      '#default_value' => $config->get('simsauth.personal_data_notice'),
      '#description' => $this->t('Check this box if you want to display personal data notice link to users.'),
    );
    $form['simsauth_auth']['personal_data_notice_nid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('specify a URL link to the personal data notice page when user is logging in.'),
      '#default_value' => $config->get('simsauth.personal_data_notice_nid'),
      '#description' => $this->t('Example:') . 'node/100',
      '#states' => array (
        'invisible' => array(
          ':input[name="personal_data_notice"]' => array( 'checked' => FALSE),
        ),
      ),
    );
    $form['actions'] = array(
		'#type' => 'actions',
        'submit' => array(
          '#type' => 'submit',
          '#value' => $this->t('Save SIMS Configuration'),
		),
    );


	return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = '';
    $config = \Drupal::configFactory()->getEditable('simsauth.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set("simsauth.$key", $value);
    }

    if (db2_test()) {
      if ($config->get('simsauth.role_sync')) {
        $all_roles = user_roles(TRUE);
        $data = [ 'id' => 'student', 'label'=> t('Student') ];
        if (!array_key_exists($data['id'], $all_roles)) {
		  $role = \Drupal\user\Entity\Role::create($data);
          $role->save();
        }
        $db2_query = 'select * from SCHDEPT where STATUS=1 order by DEPT_ID DESC';
        $rs = db2_query($db2_query);
        while (db2_fetch_row($rs)) {
          $data = [ 'id' => 'DEPT_ID' . trim(db2_result($rs, 'DEPT_ID')), 'label'=> trim(db2_result($rs, 'DEPT_NAME')) ];
          if (!array_key_exists($data['id'], $all_roles)) {
		    $role = \Drupal\user\Entity\Role::create($data);
            $role->save();
          }
        }
        db2_free_result($rs);
      }
    }
    else {
      $error .= $conn_string . '<br />' . t('IBM DB2 Database connection failed.');
    }
    $ret = ldap_test();
    if ($ret == 2) {
      $error .= t('LDAP server connected but can not login.');
    }
    if ($ret == 1) {
      $error .= t('LDAP server connecting failed.');
    }

    if ($error != '') {
      drupal_set_message($error . t('The configuration options have been saved, but you should check again!'), 'error');
      $config->set('simsauth.enable', FALSE);
    }
    else {
      drupal_set_message(t('The Server configure was setting perfectly. The configuration options have been saved.'));
      $config->set('simsauth.enable', TRUE);
    }

	$config->save();
  }
}
