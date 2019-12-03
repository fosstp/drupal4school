<?php

/**
 * @file
 * Contains \Drupal\ibmdb2\Form\ibmdb2ConfigForm.
 */

namespace Drupal\ibmdb2\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;

class ibmdb2ConfigForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ibmdb2_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('SIMS Account Database Settings');
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
      'ibmdb2.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	// Default settings
    $config = $this->config('ibmdb2.settings');

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
    $form['ibmdb2_setup'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Account Management Server Setup'),
      '#collapsible' => FALSE,
    );
    $form['ibmdb2_setup']['db2_server'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Account Management Server'),
      '#default_value' => $config->get('ibmdb2.db2_server'),
      '#description' => $this->t('Your IBM DB2 server DNS record or NAT IP. Do not input NIC IP, You should not allow the connection pass throught the edge firewall.'),
      '#required' => TRUE,
    );
    $form['ibmdb2_setup']['db2_port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SIMS Database Connect Port'),
      '#default_value' => $config->get('ibmdb2.db2_port'),
      '#description' => $this->t('Which port is the IBM DB2 server connection port?'),
      '#required' => TRUE,
    );
    $form['ibmdb2_setup']['database'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SIMS Database Name'),
      '#default_value' => $config->get('ibmdb2.database'),
      '#description' => $this->t('Which database to bind?'),
      '#required' => TRUE,
    );
    $form['ibmdb2_setup']['schema'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SIMS Database Schema'),
      '#default_value' => $config->get('ibmdb2.schema'),
      '#description' => $this->t('Which schema to log in the server?'),
      '#required' => TRUE,
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
    $config = \Drupal::configFactory()->getEditable('ibmdb2.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set("ibmdb2.$key", $value);
    }
	$config->save();

    $config = \Drupal::config('ibmdb2.settings');
    $db2info = array(
      'driver' => 'ibmdb2',
      'database' => $config->get('ibmdb2.database'),
      'hostname' => $config->get('ibmdb2.db2_server'),
      'port' => $config->get('ibmdb2.db2_port'),
      'uid' => $config->get('ibmdb2.db2_admin'),
      'pwd' => $config->get('ibmdb2.db2_pass'),
	  'currentschema' => $config->get('ibmdb2.schema'),
    );
    Database::addConnectionInfo('db2', 'sims', $db2info);
    $org = Database::setActiveConnection('db2');

    if (!Database::isActiveConnection()) {
      $error .= $conn_string . '<br />' . t('IBM DB2 Database connection failed.');
      drupal_set_message($error . t('The configuration options have been saved, but you should check again!'), 'error');
    }
    else {
      Database::setActiveConnection($org);
      drupal_set_message(t('The Server configure was setting perfectly. The configuration options have been saved.'));
    }
  }
}
