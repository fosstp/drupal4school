<?php

namespace Drupal\gsync\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class GsyncOperationForm extends FormBase
{
    public function getFormId()
    {
        return 'gsync_operation_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = \Drupal::config('gsync.settings');
        if ($config->get('enabled')) {
            $form['help'] = [
                '#markup' => '<p>進行帳號同步到 G Suite 時，會花費較久的時間，請耐心等候同步作業完成。未完成前請勿離開此頁面、重新整理頁面或是關閉瀏覽器！<br>'.
                '同步程式無法同步密碼，程序運作流程如下：<ol>'.
                '<li>以臺北市校園單一身分驗證服務的電子郵件搜尋 G Suite，帳號已存在者使用現有帳號，如果搜尋不到則自動幫您建立帳號。若學生帳號樣式設定為台北市校園單一身份驗證登入帳號，將自動把學生學號新增為郵箱別名。</li>'.
                '<li>搜尋 G Suite 群組的說明(description)欄位是否與校務行政系統裡的所屬部門名稱相同，若相同則使用該群組，如果找不到則自動幫您建立群組。（如果您已經有一個匹配的 G Suite 群組，請在說明欄輸入部門名稱，以便讓程式可以正確辨識）</li>'.
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
                '#title' => '停用離職員工或不在籍學生',
                '#description' => '如果在臺北市校園單一身分驗證服務中已經將人員設定為刪除或停用，將會停用該人員的 G Suite 帳號。',
            ];
            $form['delete_nonuse'] = [
                '#type' => 'checkbox',
                '#title' => '移除離職員工或不在籍學生',
                '#description' => '如果在臺北市校園單一身分驗證服務中已經將人員設定為刪除或停用，將會刪除該人員的 G Suite 帳號。',
            ];
            $form['log'] = [
                '#type' => 'checkbox',
                '#title' => '顯示詳細處理紀錄',
                '#description' => '預設僅顯示錯誤訊息，勾選此選項將會顯示詳細處理紀錄以便了解處理流程。',
            ];
            $form['domain'] = [
                '#type' => 'radios',
                '#title' => '請選擇要同步的帳號類型',
                '#default_value' => 0,
                '#options' => [0 => '教職員', 1 => '學生'],
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
                '#states' => [
                    'visible' => [
                        ':input[name="domain"]' => ['value' => 0],
                    ],
                ],
            ];
            $grades = all_grade();
            $gradelist = [];
            if ($grades) {
                foreach ($grades as $g) {
                    $gradelist[$g->grade] = $g->grade.'年級';
                }
            }
            $form['grade'] = [
                '#type' => 'select',
                '#title' => '請選擇要同步的年級',
                '#multiple' => false,
                '#options' => $gradelist,
                '#default_value' => 1,
                '#size' => 1,
                '#states' => [
                    'visible' => [
                        ':input[name="domain"]' => ['value' => 1],
                    ],
                ],
            ];
            if ($grades) {
                foreach ($grades as $g) {
                    $classes = get_classes_of_grade($g->grade);
                    $classlist = [];
                    if ($classes) {
                        foreach ($classes as $c) {
                            $classlist[$c->id] = $c->name;
                        }
                    }
                    $form['grade'.$g->grade] = [
                        '#type' => 'select',
                        '#title' => '要同步哪些班級的學生？',
                        '#multiple' => true,
                        '#options' => $classlist,
                        '#size' => 10,
                        '#states' => [
                            'visible' => [
                                ':input[name="domain"]' => ['value' => 1],
                                ':input[name="grade"]' => ['value' => $g->grade],
                            ],
                        ],
                    ];
                }
            }
            $form['start'] = [
                '#type' => 'button',
                '#value' => '開始同步',
                '#executes_submit_callback' => false,
                '#ajax' => [
                    'callback' => [$this, 'gsync_start'],
                ],
            ];
            $form['viewport'] = [
                '#type' => 'fieldset',
                '#collapsible' => false,
                '#collapsed' => false,
            ];
            $form['viewport']['log_div'] = [
                '#type' => 'item',
                '#title' => '詳細處理紀錄',
            ];
        } else {
            $form['help'] = [
                '#type' => 'item',
                '#title' => '提示訊息：',
                '#markup' => '請先完成 G Suite 同步模組的相關設定！',
            ];
        }

        return $form;
    }

    public function gsync_start(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        $dc = \Drupal::config('tpedu.settings')->get('api.dc');
        $config = \Drupal::config('gsync.settings');
        $google_domain = $config->get('google_domain');
        $std_account = $config->get('student_account');
        $detail_log = '';
        set_time_limit(0);
        $time_start = microtime(true);
        $domain = $form_state->getValue('domain');
        $log = $form_state->getValue('log');
        initGoogleDirectory();
        $all_groups = gs_listGroups();
        if (!$all_groups) {
            $all_groups = [];
        }
        if ($domain == 0) {
            $depts = $form_state->getValue('dept');
            foreach ($depts as $dept) {
                $teachers = get_teachers_of_unit($dept);
                if ($teachers) {
                    foreach ($teachers as $t) {
                        $groups = [];
                        $user_key = $t->email;
                        if (!strpos($user_key, $google_domain)) {
                            $user_key = $t->account.'@'.$google_domain;
                        }
                        if ($log) {
                            $detail_log .= "正在處理 $t->dept_name $t->role_name $t->realname ($user_key)......<br>";
                        }
                        $user = gs_getUser($user_key);
                        if (!$user) {
                            $result = gs_findUsers('externalId='.$t->id);
                            if ($result) {
                                $user = $result[0];
                            }
                        }
                        if ($user) {
                            $data = gs_listUserGroups($user_key);
                            if ($data) {
                                foreach ($data as $g) {
                                    $gn = $g->getEmail();
                                    if (substr($gn, 0, 6) == 'group-') {
                                        $groups[] = $g->getEmail();
                                    }
                                }
                            }
                            if ($log) {
                                $detail_log .= '使用者先前已加入以下群組：<ul>';
                                foreach ($groups as $g) {
                                    $detail_log .= "<li>$g</li>";
                                }
                                $detail_log .= '</ul>';
                            }
                            if (is_null($t->status) || $t->status == 'active') {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在更新使用者資訊中......';
                                }
                                $user = gs_syncUser($t, $user_key, $user, $form_state->getValue('password_sync'));
                                if ($user) {
                                    if ($log) {
                                        $detail_log .= '更新完成！<br>';
                                    }
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 更新失敗！<br>";
                                }
                            } elseif ($form_state->getValue('disable_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>停用</strong>這個帳號';
                                }
                                $user->setSuspended(true);
                                $user = gs_updateUser($user_key, $user);
                                if ($user) {
                                    if ($log) {
                                        $detail_log .= '帳號已停用！<br>';
                                    }
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 停用失敗！<br>";
                                }
                            } elseif ($form_state->getValue('delete_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>刪除</strong>這個帳號......';
                                }
                                $result = gs_deleteUser($user_key);
                                if ($user) {
                                    if ($log) {
                                        $detail_log .= '帳號已刪除！<br>';
                                    }
                                } else {
                                    $detail_log .= "$t->role_name $t->realname 刪除失敗！<br>";
                                }
                            }
                        } elseif (is_null($t->status) || $t->status == 'active') {
                            if ($log) {
                                $detail_log .= '無法在 G Suite 中找到這個使用者，現在正在為使用者建立 Google 帳號......';
                            }
                            $user = gs_syncUser($t, $user_key);
                            if ($user) {
                                if ($log) {
                                    $detail_log .= '建立完成！<br>';
                                }
                            } else {
                                $detail_log .= "$t->role_name $t->realname 建立失敗！<br>";
                            }
                        }
                        $jobs = get_jobs($t->uuid);
                        if ($jobs) {
                            foreach ($jobs as $job) {
                                if ($log) {
                                    $detail_log .= "<p>正在處理 $job->dept_name ......<br>";
                                }
                                $found = false;
                                if ($all_groups) {
                                    foreach ($all_groups as $group) {
                                        if ($group->getDescription() == $job->dept_name) {
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
                                } else {
                                    if ($log) {
                                        $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                    }
                                    $depgroup = 'group-A'.$job->dept_id;
                                    $group_key = $depgroup.'@'.$google_domain;
                                    $group = gs_createGroup($group_key, $job->dept_name);
                                    if ($group) {
                                        $all_groups[] = $group;
                                        if ($log) {
                                            $detail_log .= '建立成功！<br>';
                                        }
                                    } else {
                                        $detail_log .= "$job->dept_name 群組建立失敗！<br>";
                                    }
                                }
                                if (($k = array_search($group_key, $groups)) !== false) {
                                    unset($groups[$k]);
                                } else {
                                    if ($log) {
                                        $detail_log .= "正在將使用者： $job->role_name $t->realname 加入到群組裡......";
                                    }
                                    $members = gs_addMember($group_key, $user_key);
                                    if (!empty($members)) {
                                        if ($log) {
                                            $detail_log .= '加入成功！<br>';
                                        }
                                    } else {
                                        $detail_log .= "無法將使用者 $job->role_name $t->realname 加入 $job->dept_name 群組！<br>";
                                    }
                                }
                                if ($log) {
                                    $detail_log .= "<p>正在處理 $job->role_name ......<br>";
                                }
                                $found = false;
                                if ($all_groups) {
                                    foreach ($all_groups as $group) {
                                        if ($group->getDescription() == $job->role_name) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                }
                                if ($found) {
                                    $group_key = $group->getEmail();
                                    $posgroup = explode('@', $group_key)[0];
                                    if ($log) {
                                        $detail_log .= "$posgroup => 在 G Suite 中找到匹配的使用者群組！<br>";
                                    }
                                } else {
                                    if ($log) {
                                        $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                    }
                                    $posgroup = 'group-B'.$job->role_id;
                                    $group_key = $posgroup.'@'.$google_domain;
                                    $group = gs_createGroup($group_key, $job->role_name);
                                    if ($group) {
                                        $all_groups[] = $group;
                                        if ($log) {
                                            $detail_log .= '建立成功！<br>';
                                        }
                                        $groups[] = $group;
                                    } else {
                                        $detail_log .= "$job->role_name 群組建立失敗！<br>";
                                    }
                                }
                                if (($k = array_search($group_key, $groups)) !== false) {
                                    unset($groups[$k]);
                                } else {
                                    if ($log) {
                                        $detail_log .= "正在將使用者： $job->role_name $t->realname 加入到群組裡......";
                                    }
                                    $members = gs_addMember($group_key, $user_key);
                                    if (!empty($members)) {
                                        if ($log) {
                                            $detail_log .= '加入成功！<br>';
                                        }
                                    } else {
                                        $detail_log .= "無法將使用者 $job->role_name $t->realname 加入 $job->role_name 群組！<br>";
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
                            $group_key = $clsgroup.'@'.$google_domain;
                            $found = false;
                            if ($all_groups) {
                                foreach ($all_groups as $group) {
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
                            } else {
                                if ($log) {
                                    $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                                }
                                $group = gs_createGroup($group_key, "$grade年級");
                                if ($group) {
                                    $all_groups[] = $group;
                                    if ($log) {
                                        $detail_log .= '建立成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "$grade 年級群組建立失敗！<br>";
                                }
                            }
                            if (($k = array_search($group_key, $groups)) !== false) {
                                unset($groups[$k]);
                            } else {
                                if ($log) {
                                    $detail_log .= "正在將使用者： $t->role_name $t->realname 加入到群組裡......";
                                }
                                $members = gs_addMember($group_key, $user_key);
                                if (!empty($members)) {
                                    if ($log) {
                                        $detail_log .= '加入成功！<br>';
                                    }
                                } else {
                                    $detail_log .= "無法將使用者 $t->role_name $t->realname 加入 $grade 年級群組！<br>";
                                }
                            }
                        }
                        foreach ($groups as $g) {
                            if ($log) {
                                $detail_log .= "正在將使用者： $t->role_name $t->realname 從群組 $g 移除......";
                            }
                            $result = gs_removeMember($g, $user_key);
                            if ($result) {
                                if ($log) {
                                    $detail_log .= '移除成功！<br>';
                                }
                            } else {
                                $detail_log .= "無法將使用者 $t->role_name $t->realname 從群組 $g 移除！<br>";
                            }
                        }
                    }
                }
            }
        } else {
            $grade = $form_state->getValue('grade');
            $classes = $form_state->getValue('grade'.$grade);
            foreach ($classes as $class) {
                $class_name = get_class_name($class);
                if ($log) {
                    $detail_log .= "<p>正在處理 $class_name......<br>";
                }
                $stdgroup = 'class-'.$class;
                $group_key = $stdgroup.'@'.$google_domain;
                $found = false;
                if ($all_groups) {
                    foreach ($all_groups as $group) {
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
                    $members = gs_listMembers($group_key);
                    foreach ($members as $u) {
                        gs_removeMember($group_key, $u->getEmail());
                    }
                    if ($log) {
                        $detail_log .= '已經移除群組裡的所有成員！<br>';
                    }
                } else {
                    if ($log) {
                        $detail_log .= '無法在 G Suite 中找到匹配的群組，現在正在建立新的 Google 群組......';
                    }
                    $group = gs_createGroup($group_key, $class_name);
                    if ($group && $log) {
                        $detail_log .= '建立成功！<br>';
                    } else {
                        $detail_log .= "$class_name 群組建立失敗！<br>";
                    }
                }
                $students = get_students_of_class($class);
                if ($students) {
                    foreach ($students as $s) {
                        $user_alias = false;
                        if ($std_account == 'id') {
                            $user_key = $s->id.'@'.$google_domain;
                        } else {
                            if (empty($s->account)) {
                                $user_key = $s->id.'@'.$google_domain;
                            } else {
                                $user_key = $s->account.'@'.$google_domain;
                                $user_alias = $s->id.'@'.$google_domain;
                            }
                        }
                        if ($log) {
                            $detail_log .= "正在處理 $s->class $s->seat $s->realname ($user_key)......<br>";
                        }
                        $user = gs_getUser($user_key);
                        if (!$user) {
                            $result = gs_findUsers('externalId='.$s->id);
                            if ($result) {
                                $user = $result[0];
                            }
                        }
                        if ($user) {
                            if (is_null($s->status) || $s->status == 'active') {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在更新使用者資訊中......';
                                }
                                $user = gs_syncUser($s, $user_key, $user, $form_state->getValue('password_sync'));
                                if ($user) {
                                    if ($log) {
                                        $detail_log .= '更新完成！<br>';
                                    }
                                } else {
                                    $detail_log .= "$s->class $s->seat $s->realname 更新失敗！<br>";
                                }
                                if ($user_alias) {
                                    if ($log) {
                                        $detail_log .= "現在正在建立使用者別名 $user_alias ......";
                                    }
                                    $result = gs_createUserAlias($user_key, $user_alias);
                                    if ($result) {
                                        if ($log) {
                                            $detail_log .= '建立完成！<br>';
                                        }
                                    } else {
                                        $detail_log .= '別名建立失敗！<br>';
                                    }
                                }
                            } elseif ($form_state->getValue('disable_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>停用</strong>這個帳號';
                                }
                                $user->setSuspended(true);
                                $user = gs_updateUser($user_key, $user);
                                if ($user) {
                                    if ($log) {
                                        $detail_log .= '帳號已停用！<br>';
                                    }
                                } else {
                                    $detail_log .= "$s->id $s->realname 停用失敗！<br>";
                                }
                            } elseif ($form_state->getValue('delete_nonuse')) {
                                if ($log) {
                                    $detail_log .= '在 G Suite 中找到這位使用者，現在正在<strong>刪除</strong>這個帳號......';
                                }
                                $result = gs_deleteUser($user_key);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '帳號已刪除！<br>';
                                    }
                                } else {
                                    $detail_log .= "$s->id $s->realname 刪除失敗！<br>";
                                }
                            }
                        } elseif (is_null($s->status) || $s->status == 'active') {
                            if ($log) {
                                $detail_log .= '無法在 G Suite 中找到這個使用者，現在正在為使用者建立 Google 帳號......';
                            }
                            $user = gs_syncUser($s, $user_key);
                            if ($user) {
                                if ($log) {
                                    $detail_log .= '建立完成！<br>';
                                }
                            } else {
                                $detail_log .= "$s->class $s->seat $s->realname 建立失敗！<br>";
                            }
                            if ($user_alias) {
                                if ($log) {
                                    $detail_log .= "現在正在建立使用者別名 $user_alias ......";
                                }
                                $result = gs_createUserAlias($user_key, $user_alias);
                                if ($result) {
                                    if ($log) {
                                        $detail_log .= '建立完成！<br>';
                                    }
                                } else {
                                    $detail_log .= '別名建立失敗！<br>';
                                }
                            }
                        }
                        if ($log) {
                            $detail_log .= "正在將使用者： $s->class $s->seat $s->realname 加入到 $class_name 群組裡......";
                        }
                        $members = gs_addMember($group_key, $user_key);
                        if (!empty($members)) {
                            if ($log) {
                                $detail_log .= '加入成功！<br>';
                            }
                        } else {
                            $detail_log .= "將 $s->class $s->seat $s->realname 加入 $class_name 群組失敗！<br>";
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
