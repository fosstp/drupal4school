<?php

use Drupal\Core\Render\BubbleableMetadata;

function tpedu_token_info_alter(&$info)
{
    $info['tokens']['user']['gender'] = [
        'name' => '性別',
        'description' => '使用者的性別代號，1 為男、2 為女',
    ];
    $info['tokens']['user']['student'] = [
        'name' => '學生',
        'description' => '是否為學生？ 1 是、0 不是',
    ];
    $info['tokens']['user']['idno'] = [
        'name' => '身分證字號',
        'description' => '使用者的身分證字號',
    ];
    $info['tokens']['user']['birthdate'] = [
        'name' => '出生日期',
        'description' => '使用者的出生日期',
        'type' => 'date',
    ];
    $info['tokens']['user']['class'] = [
        'name' => '班級',
        'description' => '導師班級（教師身分）或就讀班級（學生身分）',
    ];
    $info['tokens']['user']['depname'] = [
        'name' => '行政部門',
        'description' => '教職員任職部門的名稱',
    ];
    $info['tokens']['user']['titlename'] = [
        'name' => '職稱',
        'description' => '教職員的職稱',
    ];
    $info['tokens']['user']['sysid'] = [
        'name' => '校務行政系統代號',
        'description' => '教師編號（教師身分）或學號（學生身分）',
    ];
    $info['tokens']['user']['seat'] = [
        'name' => '座號',
        'description' => '學生的座號',
    ];
}

/**
 * Implements hook_tokens().
 */
function tpedu_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata)
{
    $token_service = \Drupal::token();
    $bubbleable_metadata = $bubbleable_metadata ?: new BubbleableMetadata();
    if (isset($options['langcode'])) {
        $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
        $langcode = $options['langcode'];
    } else {
        $langcode = null;
    }
    $replacements = [];
    if ($type == 'user' && !empty($data['user'])) {
        /** @var \Drupal\user\UserInterface $account */
        $account = $data['user'];
        foreach ($tokens as $name => $original) {
            switch ($name) {
            case 'gender':
                $replacements[$original] = $account->tpedu->gender;
                break;

            case 'student':
                $replacements[$original] = $account->tpedu->student;
                break;

            case 'idno':
                $replacements[$original] = $account->tpedu->idno;
                break;

            case 'birthdate':
                $replacements[$original] = \Drupal::service('date.formatter')->format($account->tpedu->birthdate, 'medium', '', null, $langcode);
                break;

            case 'class':
                $replacements[$original] = $account->tpedu->class;
                break;

            case 'depname':
                $replacements[$original] = $account->tpedu->dept_name;
                break;

            case 'titlename':
                $replacements[$original] = $account->tpedu->role_name;
                break;

            case 'sysid':
                $replacements[$original] = $account->tpedu->id;
                break;

            case 'seat':
                $replacements[$original] = $account->tpedu->seat;
                break;
            }
        }
    }

    return $replacements;
}
