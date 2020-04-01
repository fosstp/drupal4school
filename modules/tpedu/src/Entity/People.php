<?php

namespace Drupal\tpedu\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the People entity.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "tpedu_people",
 *   label = "臺北市教育人員",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\tpedu\Views\PeopleViewsData",
 *     "access" = "Drupal\tpedu\PeopleAccessControlHandler",
 *   },
 *   base_table = "tpedu_people",
 *   admin_permission = "administer people entity",
 *   entity_keys = {
 *     "uuid" = "uuid",
 *     "idno" = "idno",
 *     "id" = "id",
 *   },
 *   field_ui_base_route = "entity.people.admin_form",
 * )
 */
class People extends ContentEntityBase {

    public function getFetchTime() {
        return $this->get('fetch_date')->value;
    }
    
    public function getUser() {
        $account = \Drupal::database()->query("select * from users where uuid='" . $this->get('uuid')->value . "'")->fetchObject();
        return User::load($account->id);
    }
    
    public function getUserId() {
        $account = \Drupal::database()->query("select * from users where uuid='" . $this->get('uuid')->value . "'")->fetchObject();
        return $account->id;
    }
            
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    
        $fields['uuid'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel('UUID')
        ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
        ->setDescription('臺北市教育人員唯一編碼')
        ->setTargetEntityTypeId('user');
  
        $fields['idno'] = BaseFieldDefinition::create('string')
            ->setLabel('身分證字號')
            ->setDescription('中華民國身分證或居留證號')
            ->setReadOnly(TRUE)
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -19,
            ))
            ->setDisplayConfigurable('view', TRUE);
      
        $fields['id'] = BaseFieldDefinition::create('integer')
            ->setLabel('系統代號')
            ->setDescription('校務行政系統的系統代號（教師編號或學號）')
            ->setReadOnly(TRUE)
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'integer',
                'weight' => -18,
            ))
            ->setDisplayConfigurable('view', TRUE);
      
        $fields['student'] = BaseFieldDefinition::create('boolean')
            ->setLabel('學生')
            ->setDescription('是否為學生')
            ->setReadOnly(TRUE)
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -17,
            ))
            ->setDisplayConfigurable('view', TRUE);
    
        $fields['account'] = BaseFieldDefinition::create('string')
            ->setLabel('登入帳號')
            ->setDescription('臺北市教育人員的主要登入帳號')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -16,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['realname'] = BaseFieldDefinition::create('string')
            ->setLabel('真實姓名')
            ->setDescription('臺北市教育人員的真實姓名')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -15,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['dept_id'] = BaseFieldDefinition::create('string')
            ->setLabel('部門代號')
            ->setDescription('臺北市教育人員所屬的處室代號')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -14,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['dept_name'] = BaseFieldDefinition::create('string')
            ->setLabel('部門名稱')
            ->setDescription('臺北市教育人員所屬的處室名稱')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -13,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['role_id'] = BaseFieldDefinition::create('string')
            ->setLabel('職務代號')
            ->setDescription('臺北市教育人員的職務代號')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -12,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['role_name'] = BaseFieldDefinition::create('string')
            ->setLabel('職務名稱')
            ->setDescription('臺北市教育人員的職務名稱')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -11,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['birthdate'] = BaseFieldDefinition::create('datetime')
            ->setLabel('出生日期')
            ->setDescription('臺北市教育人員的出生日期')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'datetime',
                'weight' => -10,
            ))
            ->setDisplayConfigurable('view', TRUE);    

        $fields['gender'] = BaseFieldDefinition::create('list_string')
            ->setLabel('性別')
            ->setDescription('臺北市教育人員的性別')
            ->setSettings(array(
                'allowed_values' => array(
                    '0' => '未知',
                    '1' => '男',
                    '2' => '女',
                    '9' => '其它',
                ),
            ))
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'list_default',
                'weight' => -9,
            ))
            ->setDisplayConfigurable('view', TRUE);
    
        $fields['mobile'] = BaseFieldDefinition::create('string')
            ->setLabel('行動電話')
            ->setDescription('臺北市教育人員的行動電話')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -8,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['telephone'] = BaseFieldDefinition::create('string')
            ->setLabel('有線電話')
            ->setDescription('臺北市教育人員的有線電話')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -7,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['address'] = BaseFieldDefinition::create('string')
            ->setLabel('郵寄地址')
            ->setDescription('臺北市教育人員的郵寄地址')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -6,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel('電子郵件信箱')
            ->setDescription('臺北市教育人員的電子郵件信箱')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'email',
                'weight' => -5,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['www'] = BaseFieldDefinition::create('string')
            ->setLabel('個人首頁')
            ->setDescription('臺北市教育人員的個人首頁')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -4,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['class'] = BaseFieldDefinition::create('string')
            ->setLabel('就讀或任教班級')
            ->setDescription('臺北市教育人員的就讀班級或擔任導師的班級')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -3,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['seat'] = BaseFieldDefinition::create('string')
            ->setLabel('學生座號')
            ->setDescription('臺北市教育人員的學生座號')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -2,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['character'] = BaseFieldDefinition::create('string')
            ->setLabel('特殊身分註記')
            ->setDescription('臺北市教育人員的特殊身分註記')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -1,
            ))
            ->setDisplayConfigurable('view', TRUE);

        $fields['fetched'] = BaseFieldDefinition::create('timestamp')
            ->setLabel('取得時間')
            ->setDescription('資料從 ldap.tp.edu.tw 取得的時間');
    
        return $fields;
    }

}