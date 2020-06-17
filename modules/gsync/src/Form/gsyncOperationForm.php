<?php

namespace Drupal\gsync\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class gsyncOperationForm extends FormBase
{
    private $group_reset = array();

    public function getFormId()
    {
        return 'gsync_operation_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        global $base_url;
        $config = \Drupal::config('gsync.settings');

        if ($config->get('enabled')) {
            $form['help'] = array(
                '#markup' => '<p>進行帳號同步到 G Suite 時，會花費較久的時間，請耐心等候同步作業完成。未完成前請勿離開此頁面、重新整理頁面或是關閉瀏覽器！<br>'.
                '<ol>同步程式無法同步密碼，程序運作流程如下：'.
                '<li>以臺北市教育人員單一身分驗證服務的帳號搜尋 G Suite，帳號相同者使用現有帳號，如果搜尋不到則自動幫您建立帳號。</li>'.
                '<li>搜尋 G Suite 群組的說明(description)欄位是否與校務行政系統裡的所屬部門名稱相同，若相同則使用該群組，如果找不到則自動幫您建立群組。（如果您已經有一個匹配的 G Suite 群組，請在說明欄輸入部門名稱，以便讓程式可以正確辨識）</li>'.
                '<li>移除群組裡所有舊的成員。</li>'.
                '<li>重新將使用者加入到群組裡。</li></ol></p>',
            );
            $form['password_sync'] = array(
                '#type' => 'checkbox',
                '#title' => '密碼重設為預設值',
                '#description' => '注意：預設密碼為身分證字號後六碼',
            );
            $form['disable_nonuse'] = array(
                '#type' => 'checkbox',
                '#title' => '停用離職員工或不在籍學生',
                '#description' => '如果在臺北市教育人員單一身分驗證服務中已經將人員設定為刪除或停用，將會停用該人員的 G Suite 帳號。',
            );
            $form['delete_nonuse'] = array(
                '#type' => 'checkbox',
                '#title' => '移除離職員工或不在籍學生',
                '#description' => '如果在臺北市教育人員單一身分驗證服務中已經將人員設定為刪除或停用，將會刪除該人員的 G Suite 帳號。',
            );
            $form['log'] = array(
                '#type' => 'checkbox',
                '#title' => '顯示詳細處理紀錄',
                '#description' => '預設僅顯示錯誤訊息，勾選此選項將會顯示詳細處理紀錄以便了解處理流程。',
            );
            $form['domain'] = array(
                '#type' => 'radios',
                '#title' => '請選擇要同步的帳號類型',
                '#default_value' => 0,
                '#options' => array(0 => '教職員', 1 => '學生'),
            );
            $units = all_units();
            $deplist = array();
            if ($units) {
                foreach ($units as $u) {
                    $deplist[$u->id] = $u->name;
                }
            }
            $form['dept'] = array(
                '#type' => 'select',
                '#title' => '要同步哪些行政單位？',
                '#multiple' => true,
                '#options' => $deplist,
                '#size' => 15,
                '#states' => array(
                    'visible' => array(
                        ':input[name="domain"]' => array('value' => 0),
                    ),
                ),
            );
            $grades = all_grade();
            $gradelist = array();
            if ($grades) {
                foreach ($grades as $g) {
                    $gradelist[$g->grade] = $g->grade.'年級';
                }
            }
            $form['grade'] = array(
                '#type' => 'select',
                '#title' => '請選擇要同步的年級',
                '#multiple' => false,
                '#options' => $gradelist,
                '#default_value' => 1,
                '#size' => 1,
                '#states' => array(
                    'visible' => array(
                        ':input[name="domain"]' => array('value' => 1),
                    ),
                ),
            );
            if ($grades) {
                foreach ($grades as $g) {
                    $classes = get_classes_of_grade($g->grade);
                    $classlist = array();
                    if ($classes) {
                        foreach ($classes as $c) {
                            $classlist[$c->id] = $c->name;
                        }
                    }
                    $form['grade'.$g->grade] = array(
                        '#type' => 'select',
                        '#title' => '要同步哪些班級的學生？',
                        '#multiple' => true,
                        '#options' => $classlist,
                        '#size' => 1,
                        '#states' => array(
                            'visible' => array(
                                ':input[name="domain"]' => array('value' => 1),
                                ':input[name="grade"]' => array('value' => $g->grade),
                            ),
                        ),
                    );
                }
            }
            $form['start'] = array(
                '#type' => 'button',
                '#value' => '開始同步',
                '#executes_submit_callback' => false,
                '#ajax' => array(
                    'callback' => [$this, 'gsync_start'],
                    'wrapper' => 'edit-log-div',
                ),
            );
            $form['viewport'] = array(
                '#type' => 'fieldset',
                '#title' => '詳細處理紀錄',
                '#collapsible' => false,
                '#collapsed' => false,
            );
            $form['viewport']['log_div'] = array(
                '#type' => 'item',
            );
        } else {
            $form['help'] = array(
                '#type' => 'item',
                '#title' => '提示訊息：',
                '#markup' => '請先完成 G Suite 同步模組的相關設定！',
            );
        }

        return $form;
    }

    public function gsync_start(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $config = \Drupal::config('gsync.settings');
        $detail_log = '';
        $group_reset = array();
        set_time_limit(0);
        $time_start = microtime(true);
        $domain = $form_state->getValue('domain');
        $log = $form_state->getValue('log');
        initGoogleService();
        if ($domain == 0) {
            $groups = gs_listGroups();
            $depts = $form_state->getValue('dept');
            foreach ($depts as $dept) {
                $teachers = get_teachers_of_unit($dept);
                if ($teachers) {
                    foreach ($teachers as $t) {
                        if ($log) {
                            $detail_log .= "正在處理 $t->dept_name $t->role_name $t->realname ($t->account)......<br>";
                        }
                        $user_key = $t->account.'@'.$config->get('google_domain');
                        $user = gs_getUser($user_key);
                        if ($user) {
                            if (is_null($t->status) || $t->status == 'active') {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在更新使用者資訊中......';
                                }
                                $user = gs_syncUser($t, $user);
                                if ($form_state->getValue('password_sync')) {
                                    $user->setHashFunction('SHA-1');
                                    $user->setPassword(sha1(substr($t->idno, -6)));
                                    $user = gs_updateUser($user_key, $user);
                                }
                                if ($user && $log) {
                                    $detail_log .= '更新完成！<br>';
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 更新失敗！<br>";
                                }
                            } elseif ($form_state->getValue('disable_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>停用</strong>這個帳號';
                                }
                                $user->setSuspended(true);
                                $user = gs_updateUser($user_key, $user);
                                if ($user && $log) {
                                    $detail_log .= '帳號已停用！<br>';
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 停用失敗！<br>";
                                }
                            } elseif ($form_state->getValue('delete_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>刪除</strong>這個帳號......';
                                }
                                $result = gs_deleteUser($user_key);
                                if ($result && $log) {
                                    $detail_log .= '帳號已刪除！<br>';
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 刪除失敗！<br>";
                                }
                            }
                        } elseif (is_null($t->status) || $t->status == 'active') {
                            if ($log) {
                                $detail_log .= '無法在 G Suite 中找到這個使用者，現在正在為使用者建立 Google 帳號......';
                            }
                            $user = gs_createUser($t, $user_key);
                            if ($user && $log) {
                                $detail_log .= '建立完成！<br>';
                            } else {
                                $detail_log .= "$t->role_name $t->realname 建立失敗！<br>";
                            }
                        }
                        if (!empty($t->dept_id) && !empty($t->role_id)) {
                            if ($log) {
                                $detail_log .= "<p>正在處理 $t->dept_name ......<br>";
                            }
                            $found = false;
                            if ($groups) {
                                foreach ($groups as $group) {
                                    if ($group->getDescription() == $t->dept_name) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if ($found) {
                                $group_key = $group->getEmail();
                                $depgroup = explode('@', $group_key)[0];
                                if ($log) {
                                    $detail_log .= "$depgroup => 在 G Suite 中找到匹配的使用者群組！<br>";
                                }
                                if (!in_array($depgroup, $this->group_reset)) {
                                    $members = gs_listMembers($group_key);
                                    gs_removeMembers($group_key, $members);
                                    if ($log) {
                                        $detail_log .= '已經移除群組裡的所有成員！<br>';
                                    }
                                    $this->group_reset[] = $depgroup;
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                }
                                $depgroup = 'group-A'.$t->dept_id;
                                $group_key = $depgroup.'@'.$config->get('google_domain');
                                $group = gs_createGroup($group_key, $t->dept_name);
                                if ($group && $log) {
                                    $detail_log .= '建立成功！<br>';
                                    $groups[] = $group;
                                } else {
                                    $detail_log .= "$t->dept_name 群組建立失敗！<br>";
                                }
                            }
                            if ($log) {
                                $detail_log .= "正在將使用者： $t->account 加入到群組裡......";
                            }
                            $members = gs_addMembers($group_key, array($user_key));
                            if (!empty($members) && $log) {
                                $detail_log .= '加入成功！<br>';
                            } else {
                                $detail_log .= "無法將使用者 $t->role_name $t->realname 加入 $t->dept_name 群組！<br>";
                            }

                            if ($log) {
                                $detail_log .= "<p>正在處理 $t->role_name ......<br>";
                            }
                            $found = false;
                            if ($groups) {
                                foreach ($groups as $group) {
                                    if ($group->getDescription() == $t->role_name) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if ($found) {
                                $group_key = $group->getEmail();
                                $posgroup = explode('@', $group_key)[0];
                                if ($log) {
                                    $detail_log .= "$depgroup => 在 G Suite 中找到匹配的使用者群組！";
                                }
                                if (!in_array($posgroup, $this->group_reset)) {
                                    $members = gs_listMembers($group_key);
                                    gs_removeMembers($group_key, $members);
                                    if ($log) {
                                        $detail_log .= '已經移除群組裡的所有成員！<br>';
                                    }
                                    $this->group_reset[] = $posgroup;
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                }
                                $posgroup = 'group-B'.$t->role_id;
                                $group_key = $posgroup.'@'.$config->get('google_domain');
                                $group = gs_createGroup($group_key, $t->role_name);
                                if ($group && $log) {
                                    $detail_log .= '建立成功！<br>';
                                    $groups[] = $group;
                                } else {
                                    $detail_log .= "$t->role_name 群組建立失敗！<br>";
                                }
                            }
                            if ($log) {
                                $detail_log .= "正在將使用者： $t->account 加入到群組裡......";
                            }
                            $members = gs_addMembers($group_key, array($user_key));
                            if (!empty($members) && $log) {
                                $detail_log .= '加入成功！<br>';
                            } else {
                                $detail_log .= "無法將使用者 $t->role_name $t->realname 加入 $t->role_name 群組！<br>";
                            }
                        }
                        if (!empty($t->class)) {
                            if ($log) {
                                $detail_log .= '<p>正在處理 '.substr($t->class, 0, 1).'年級......<br>';
                            }
                            $clsgroup = 'group-C'.substr($t->class, 0, 1);
                            $group_key = $clsgroup.'@'.$config->get('google_domain');
                            $found = false;
                            if ($groups) {
                                foreach ($groups as $group) {
                                    if ($group->getEmail() == $group_key) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if ($found) {
                                if ($log) {
                                    $detail_log .= "$clsgroup => 在 G Suite 中找到匹配的使用者群組！......<br>";
                                }
                                if (!in_array($clsgroup, $this->group_reset)) {
                                    $members = gs_listMembers($group_key);
                                    gs_removeMembers($group_key, $members);
                                    if ($log) {
                                        $detail_log .= '已經移除群組裡的所有成員！<br>';
                                    }
                                    $this->group_reset[] = $clsgroup;
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                }
                                $group = gs_createGroup($group_key, substr($t->class, 0, 1).'年級');
                                if ($group && $log) {
                                    $detail_log .= '建立成功！<br>';
                                    $groups[] = $group;
                                } else {
                                    $detail_log .= substr($t->class, 0, 1).'年級群組建立失敗！<br>';
                                }
                                if ($log) {
                                    $detail_log .= "正在將使用者： $t->account 加入到群組裡......";
                                }
                                $members = gs_addMembers($group_key, array($user_key));
                                if (!empty($members) && $log) {
                                    $detail_log .= '加入成功！<br>';
                                } else {
                                    $detail_log .= "無法將使用者 $t->role_name $t->realname 加入 ".substr($t->class, 0, 1).'年級群組！<br>';
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $grade = $form_state->getValue('grade');
            $classes = $form_state->getValue('grade'.$grade);
            foreach ($classes as $class) {
                $students = get_students_of_class($class);
                if ($students) {
                    foreach ($students as $s) {
                        if ($log) {
                            $detail_log .= "正在處理 $s->class $s->seat $s->realname ($s->account)......<br>";
                        }
                        $user_key = $s->account.'@'.$config->get('google_domain');
                        $user = gs_getUser($user_key);
                        if ($user) {
                            if (is_null($s->status) || $s->status == 'active') {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在更新使用者資訊中......';
                                }
                                $user = gs_syncUser($s, $user);
                                if ($form_state->getValue('password_sync')) {
                                    $user->setHashFunction('SHA-1');
                                    $user->setPassword(sha1(substr($s->idno, -6)));
                                    $user = gs_updateUser($user_key, $user);
                                }
                                if ($user && $log) {
                                    $detail_log .= '更新完成！<br>';
                                } else {
                                    $detail_log .= "$s->class $s->seat $s->realname 更新失敗！<br>";
                                }
                            } elseif ($form_state->getValue('disable_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>停用</strong>這個帳號';
                                }
                                $user->setSuspended(true);
                                $user = gs_updateUser($user_key, $user);
                                if ($user && $log) {
                                    $detail_log .= '帳號已停用！<br>';
                                } else {
                                    $detail_log .= "$s->id $s->realname 停用失敗！<br>";
                                }
                            } elseif ($form_state->getValue('delete_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>刪除</strong>這個帳號......';
                                }
                                $result = gs_deleteUser($user_key);
                                if ($result && $log) {
                                    $detail_log .= '帳號已刪除！<br>';
                                } else {
                                    $detail_log .= "$s->id $s->realname 刪除失敗！<br>";
                                }
                            }
                        } elseif (is_null($s->status) || $s->status == 'active') {
                            if ($log) {
                                $detail_log .= '無法在 G Suite 中找到這個使用者，現在正在為使用者建立 Google 帳號......';
                            }
                            $user = gs_createUser($s, $user_key);
                            if ($user && $log) {
                                $detail_log .= '建立完成！<br>';
                            } else {
                                $detail_log .= "$s->class $s->seat $s->realname 建立失敗！<br>";
                            }
                        }

                        if (!empty($s->class)) {
                            if ($log) {
                                $detail_log .= "<p>正在處理 $s->dept_name......<br>";
                            }
                            $stdgroup = 'class-'.$s->class;
                            $group_key = $stdgroup.'@'.$config->get('google_domain');
                            $found = false;
                            if ($groups) {
                                foreach ($groups as $group) {
                                    if ($group->getEmail() == $group_key) {
                                        $found = true;
                                        break;
                                    }
                                }
                            }
                            if ($found) {
                                if ($log) {
                                    $detail_log .= "$stdgroup => 在 G Suite 中找到匹配的使用者群組！......<br>";
                                }
                                if (!in_array($stdgroup, $this->group_reset)) {
                                    $members = gs_listMembers($group_key);
                                    gs_removeMembers($group_key, $members);
                                    if ($log) {
                                        $detail_log .= '已經移除群組裡的所有成員！<br>';
                                    }
                                    $this->group_reset[] = $stdgroup;
                                }
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                }
                                $group = gs_createGroup($group_key, $s->dept_name);
                                if ($group && $log) {
                                    $detail_log .= '建立成功！<br>';
                                    $groups[] = $group;
                                } else {
                                    $detail_log .= "$s->dept_name 群組建立失敗！<br>";
                                }
                                if ($log) {
                                    $detail_log .= "正在將使用者： $s->account 加入到群組裡......";
                                }
                                $members = gs_addMembers($group_key, array($user_key));
                                if (!empty($members) && $log) {
                                    $detail_log .= '加入成功！<br>';
                                } else {
                                    $detail_log .= "將 $s->class $s->seat $s->realname 加入 $s->dept_name 群組失敗！<br>";
                                }
                            }
                        }
                    }
                }
            }
        }
        $time_end = microtime(true);
        $time_spend = $time_end - $time_start;
        $detail_log .= "<br>總共花費 $time_spend 秒";
        $response->addCommand(new HtmlCommand('edit-log-div', $detail_log));

        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }
}
