<?php

namespace Drupal\adsync\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class AdsyncOperationForm extends FormBase
{
    public function getFormId()
    {
        return 'adsync_operation_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = \Drupal::config('adsync.settings');
        if ($config->get('enabled')) {
            $form['help'] = [
                '#markup' => '<p>將臺北市校園單一身分驗證服務中的<strong>教師帳號</strong>同步到 AD，作業流程需要花費較久的時間，請耐心等候同步作業完成。未完成前請勿離開此頁面、重新整理頁面或是關閉瀏覽器！<br>'.
                '同步程式無法同步密碼，程序運作流程如下：<ol>'.
                '<li>以臺北市校園單一身分驗證服務的自訂帳號搜尋 AD，帳號已存在者使用現有帳號，如果搜尋不到則自動幫您建立與登入單一身分驗證服務相同的帳號。</li>'.
                '<li>搜尋 AD 群組的說明(description)欄位是否與校務行政系統裡的所屬部門名稱相同，若相同則使用該群組，如果找不到則自動幫您建立群組。（如果您已經有一個匹配的群組，請在說明欄輸入部門名稱，以便讓程式可以正確辨識）</li>'.
                '<li>檢查使用者是否已經在群組裡，若否則將使用者加入。</li>'.
                '<li>將使用者退出其它群組。</li></ol></p>',
            ];
            $form['password_sync'] = [
                '#type' => 'checkbox',
                '#title' => '密碼重設為預設值',
                '#description' => '注意：預設密碼為身分證字號後六碼',
            ];
            $form['disable_nonuse'] = [
                '#type' => 'checkbox',
                '#title' => '停用離職員工',
                '#description' => '如果在臺北市校園單一身分驗證服務中已經將人員設定為刪除或停用，將會停用該人員的 Windows 網域帳號。',
            ];
            $form['delete_nonuse'] = [
                '#type' => 'checkbox',
                '#title' => '移除離職員工',
                '#description' => '如果在臺北市校園單一身分驗證服務中已經將人員設定為刪除或停用，將會刪除該人員的 Windows 網域帳號。',
            ];
            $form['log'] = [
                '#type' => 'checkbox',
                '#title' => '顯示詳細處理紀錄',
                '#description' => '預設僅顯示錯誤訊息，勾選此選項將會顯示詳細處理紀錄以便了解處理流程。',
            ];
            $units = all_units();
            $deplist = [];
            if ($units) {
                foreach ($units as $u) {
                    $deplist[$u->id] = $u->name;
                }
            }
            $form['dept'] = [
                '#type' => 'select',
                '#title' => '要同步哪些行政單位？',
                '#multiple' => true,
                '#options' => $deplist,
                '#size' => 15,
            ];
            $form['start'] = [
                '#type' => 'button',
                '#value' => '開始同步',
                '#executes_submit_callback' => false,
                '#ajax' => [
                    'callback' => [$this, 'adsync_start'],
                    'wrapper' => 'edit-log-div',
                ],
            ];
            $form['viewport'] = [
                '#type' => 'fieldset',
                '#title' => '詳細處理紀錄',
                '#collapsible' => false,
                '#collapsed' => false,
            ];
            $form['viewport']['log_div'] = [
                '#type' => 'item',
            ];
        } else {
            $form['help'] = [
                '#type' => 'item',
                '#title' => '提示訊息：',
                '#markup' => '請先完成 AD 同步模組的相關設定！',
            ];
        }

        return $form;
    }

    public function adsync_start(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $config = \Drupal::config('adsync.settings');
        $base_dn = $config->get('users_dn');
        $detail_log = '';
        set_time_limit(0);
        $time_start = microtime(true);
        $depts = $form_state->getValue('dept');
        $log = $form_state->getValue('log');
        foreach ($depts as $dept) {
            $teachers = get_teachers_of_unit($dept);
            if ($teachers) {
                foreach ($teachers as $t) {
                    if ($log) {
                        $detail_log .= "正在處理 $t->dept_name $t->role_name $t->realname ($t->account)......<br>";
                    }
                    $groups = [];
                    $user = ad_getUser($t->account);
                    if (!$user) {
                        $user = ad_findUser('description='.$t->idno);
                    }
                    if ($user) {
                        $user_dn = $user['distinguishedname'][0];
                        $groups = $user['memberof'];
                        foreach ($groups as $k => $g) {
                            if (substr($g, 0, 9) != 'CN=group-') {
                                unset($groups[$k]);
                            }
                        }
                        if ($log) {
                            $detail_log .= '使用者先前已加入以下群組：<ul>';
                            foreach ($groups as $g) {
                                $detail_log .= '<li>'.$g.'</li>';
                            }
                            $detail_log .= '</ul>';
                        }
                        if (is_null($t->status) || $t->status == 'active') {
                            if ($log) {
                                $detail_log .= '在 AD 中找到這位使用者，現在正在更新使用者資訊中......';
                            }
                            $result = ad_syncUser($t, $user_dn);
                            if ($form_state->getValue('password_sync')) {
                                ad_changePass($user_dn, substr($t->idno, -6));
                            }
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '更新完成！<br>';
                                }
                            } else {
                                $detail_log .= "$t->role_name $t->realname 更新失敗！".ad_error().'<br>';
                            }
                        } elseif ($form_state->getValue('disable_nonuse')) {
                            if ($log) {
                                $detail_log .= '在 AD 中找到這位使用者，現在正在<strong>停用</strong>這個帳號';
                            }
                            $result = ad_lockUser($user_dn);
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '帳號已停用！<br>';
                                }
                            } else {
                                $detail_log .= "$t->role_name $t->realname 停用失敗！<br>";
                            }
                        } elseif ($form_state->getValue('delete_nonuse')) {
                            if ($log) {
                                $detail_log .= '在 AD 中找到這位使用者，現在正在<strong>刪除</strong>這個帳號......';
                            }
                            $result = ad_deleteUser($user_dn);
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '帳號已刪除！<br>';
                                }
                            } else {
                                $detail_log .= "$t->role_name $t->realname 刪除失敗！<br>";
                            }
                        }
                    } elseif (is_null($t->status) || $t->status == 'active') {
                        if ($log) {
                            $detail_log .= '無法在 AD 中找到這個使用者，現在正在為使用者建立帳號......';
                        }
                        $user_dn = 'cn='.$t->account.",$base_dn";
                        $result = ad_createUser($t, $user_dn);
                        if ($result) {
                            if ($log) {
                                $detail_log .= '建立完成！<br>';
                            }
                        } else {
                            $detail_log .= "$t->role_name $t->realname 建立失敗！".ad_error().'<br>';
                        }
                    }
                    $jobs = get_jobs($t->uuid);
                    if ($jobs) {
                        foreach ($jobs as $job) {
                            if ($log) {
                                $detail_log .= "<p>正在處理 $job->dept_name ......<br>";
                            }
                            $group = ad_findGroup($job->dept_name);
                            if ($group) {
                                $group_dn = $group['distinguishedname'][0];
                                $depgroup = $group['samaccountname'][0];
                                if ($log) {
                                    $detail_log .= "$group_dn => 在 AD 中找到匹配的使用者群組！<br>";
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 AD 中找到匹配的群組，現在正在建立新的使用者群組......';
                                }
                                $depgroup = 'group-A'.$job->dept_id;
                                $group_dn = "cn=$depgroup,$base_dn";
                                $result = ad_createGroup($depgroup, $group_dn, $job->dept_name);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '建立成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "$job->dept_name 群組建立失敗！".ad_error().'<br>';
                                }
                            }
                            if (($k = array_search($group_dn, $groups)) !== false) {
                                unset($groups[$k]);
                            } else {
                                if ($log) {
                                    $detail_log .= "正在將使用者： $job->role_name $t->realname 加入到群組裡......";
                                }
                                $result = ad_addMember($group_dn, $user_dn);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '加入成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "無法將使用者 $job->role_name $t->realname 加入 $job->dept_name 群組！".ad_error().'<br>';
                                }
                            }
                            if ($log) {
                                $detail_log .= "<p>正在處理 $job->role_name ......<br>";
                            }
                            $group = ad_findGroup($job->role_name);
                            if ($group) {
                                $group_dn = $group['distinguishedname'][0];
                                $posgroup = $group['samaccountname'][0];
                                if ($log) {
                                    $detail_log .= "$group_dn => 在 AD 中找到匹配的使用者群組！<br>";
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 AD 中找到匹配的群組，現在正在建立新的使用者群組......';
                                }
                                $posgroup = 'group-B'.$job->role_id;
                                $group_dn = "cn=$posgroup,$base_dn";
                                $result = ad_createGroup($posgroup, $group_dn, $job->role_name);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '建立成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "$job->role_name 群組建立失敗！".ad_error().'<br>';
                                }
                            }
                            if (($k = array_search($group_dn, $groups)) !== false) {
                                unset($groups[$k]);
                            } else {
                                if ($log) {
                                    $detail_log .= "正在將使用者： $job->role_name $t->realname 加入到群組裡......";
                                }
                                $result = ad_addMember($group_dn, $user_dn);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '加入成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "無法將使用者 $job->role_name $t->realname 加入 $job->role_name 群組！".ad_error().'<br>';
                                }
                            }
                        }
                    }
                    if (!empty($t->class)) {
                        if ($log) {
                            $detail_log .= '<p>正在處理 '.substr($t->class, 0, 1).'年級......<br>';
                        }
                        $grade = substr($t->class, 0, 1);
                        switch ($grade) {
                                case 1:
                                    $clsgroup = 'group-ca';
                                    break;
                                case 2:
                                    $clsgroup = 'group-cb';
                                    break;
                                case 3:
                                    $clsgroup = 'group-cc';
                                    break;
                                case 4:
                                    $clsgroup = 'group-cd';
                                    break;
                                case 5:
                                    $clsgroup = 'group-ce';
                                    break;
                                case 6:
                                    $clsgroup = 'group-cf';
                                    break;
                                default:
                                    $clsgroup = 'group-C'.$grade;
                            }
                        $group_dn = "cn=$clsgroup,$base_dn";
                        $group = ad_getGroup($clsgroup);
                        if ($group) {
                            if ($log) {
                                $detail_log .= "$clsgroup => 在 AD 中找到匹配的使用者群組！......<br>";
                            }
                        } else {
                            if ($log) {
                                $detail_log .= '無法在 AD 中找到匹配的群組，現在正在建立新的使用者群組......';
                            }
                            $result = ad_createGroup($clsgroup, $group_key, "$grade年級");
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '建立成功！<br>';
                                }
                            } else {
                                $detail_log .= "$grade 年級群組建立失敗！".ad_error().'<br>';
                            }
                        }
                        if (($k = array_search($group_dn, $groups)) !== false) {
                            unset($groups[$k]);
                        } else {
                            if ($log) {
                                $detail_log .= "正在將使用者： $t->role_name $t->realname 加入到群組裡......";
                            }
                            $result = ad_addMember($group_dn, $user_dn);
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '加入成功！<br>';
                                }
                            } else {
                                $detail_log .= "無法將使用者 $t->role_name $t->realname 加入 $grade 年級群組！".ad_error().'<br>';
                            }
                        }
                    }
                    foreach ($groups as $g) {
                        if ($log) {
                            $detail_log .= "正在將使用者： $t->role_name $t->realname 從群組 $g 移除......";
                        }
                        $result = ad_removeMember($g, $user_dn);
                        if ($result) {
                            if ($log) {
                                $detail_log .= '移除成功！<br>';
                            }
                        } else {
                            $detail_log .= "無法將使用者 $t->role_name $t->realname 從群組 $g 移除！".ad_error().'<br>';
                        }
                    }
                }
            }
        }
        $time_end = microtime(true);
        $time_spend = $time_end - $time_start;
        $detail_log = '<div id="edit-log-div" class="form-item">'.$detail_log."<p>總共花費 $time_spend 秒</p></div>";
        $response->addCommand(new ReplaceCommand('#edit-log-div', $detail_log));

        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
