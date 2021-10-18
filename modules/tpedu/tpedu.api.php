<?php

function current_seme()
{
    if (date('m') > 7) {
        $year = date('Y') - 1911;
        $seme = 1;
    } elseif (date('m') < 2) {
        $year = date('Y') - 1912;
        $seme = 1;
    } else {
        $year = date('Y') - 1912;
        $seme = 2;
    }

    return ['year' => $year, 'seme' => $seme];
}

function is_phone($str)
{
    if (preg_match('/^09[0-9]{8}$/', $str)) {
        return true;
    } else {
        return false;
    }
}

function get_tokens($auth_code)
{
    global $base_url;
    $config = \Drupal::config('tpedu.settings');
    $response = \Drupal::httpClient()->post($config->get('api.token'), [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => $config->get('client_id'),
            'client_secret' => $config->get('client_secret'),
            'redirect_uri' => $config->get('call_back'),
            'code' => $auth_code,
        ],
    ]);
    $data = json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
        $tempstore = \Drupal::service('tempstore.private')->get('tpedu');
        $tempstore->set('expires_in', time() + $data->expires_in);
        $tempstore->set('access_token', $data->access_token);
        $tempstore->set('refresh_token', $data->refresh_token);
    } else {
        \Drupal::logger('tpedu')->error('oauth2 token response =>'.$response->getBody());

        return false;
    }
}

function refresh_tokens()
{
    $tempstore = \Drupal::service('tempstore.private')->get('tpedu');
    if ($tempstore->get('refresh_token') && $tempstore->get('expires_in') < time()) {
        $config = \Drupal::config('tpedu.settings');
        $response = \Drupal::httpClient()->post($config->get('api.token'), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $config->get('client_id'),
                'client_secret' => $config->get('client_secret'),
                'refresh_token' => $tempstore->get('refresh_token'),
                'scope' => 'user',
            ],
        ]);
        $data = json_decode($response->getBody());
        if ($response->getStatusCode() == 200) {
            $tempstore = \Drupal::service('tempstore.private')->get('tpedu');
            $tempstore->set('expires_in', time() + $data->expires_in);
            $tempstore->set('access_token', $data->access_token);
            $tempstore->set('refresh_token', $data->refresh_token);
        } else {
            \Drupal::logger('tpedu')->error('oauth2 token response =>'.$response->getBody());

            return false;
        }
    }
}

function who()
{
    $config = \Drupal::config('tpedu.settings');
    $tempstore = \Drupal::service('tempstore.private')->get('tpedu');
    if ($tempstore->get('access_token')) {
        $response = \Drupal::httpClient()->get($config->get('api.login'), [
            'headers' => ['Authorization' => 'Bearer '.$tempstore->get('access_token')],
        ]);
        $user = json_decode($response->getBody());
        if ($response->getStatusCode() == 200) {
            return $user->uuid;
        } else {
            \Drupal::logger('tpedu')->error('oauth2 user response =>'.$response->getBody());

            return false;
        }
    }

    return false;
}

function api($which, array $replacement = [])
{
    $config = \Drupal::config('tpedu.settings');
    $dataapi = $config->get('api.'.$which);
    if ($which == 'find_users') {
        if (!empty($replacement)) {
            if (array_key_exists('dc', $replacement)) {
                $dc = $replacement['dc'];
                unset($replacement['dc']);
            } else {
                $dc = $config->get('api.dc');
            }
            $dataapi .= '?';
            foreach ($replacement as $key => $data) {
                $dataapi .= $key.'='.$data.'&';
            }
            $dataapi = substr($dataapi, 0, -1);
        }
        $dataapi = str_replace('{dc}', $dc, $dataapi);
    } else {
        if (!array_key_exists('dc', $replacement)) {
            $replacement['dc'] = $config->get('api.dc');
        }
        $search = [];
        $values = [];
        foreach ($replacement as $key => $data) {
            $search[] = '{'.$key.'}';
            $values[] = $data;
        }
        $dataapi = str_replace($search, $values, $dataapi);
    }
    $response = \Drupal::httpClient()->get($dataapi, [
        'headers' => ['Authorization' => 'Bearer '.$config->get('admin_token')],
        'http_errors' => false,
    ]);
    $json = json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
        return $json;
    } else {
        \Drupal::logger('tpedu')->error('oauth2 api response:'.$dataapi.'=>'.$response->getBody());

        return false;
    }
}

function alle($which, array $replacement = null)
{
    $config = \Drupal::config('tpedu.settings');
    $dataapi = $config->get('alle.'.$which);
    $replacement['sid'] = $config->get('alle.sid');
    $search = [];
    $values = [];
    foreach ($replacement as $key => $data) {
        $search[] = '{'.$key.'}';
        $values[] = $data;
    }
    $dataapi = str_replace($search, $values, $dataapi);
    $response = \Drupal::httpClient()->get($dataapi, [
        'headers' => ['Authorization' => 'Special ip '.$config->get('alle_project')],
        'http_errors' => false,
    ]);
    $json = json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
        return $json->list;
    } else {
        \Drupal::logger('tpedu')->error('oauth2 api response:'.$dataapi.'=>'.$response->getBody());

        return false;
    }
}

function profile()
{
    $user = api('profile');
    if (!empty($user)) {
        return $user;
    }

    return false;
}

function fetch_user($uuid)
{
    $database = \Drupal::database();
    $database->delete('tpedu_people')->condition('uuid', $uuid)->execute();
    $database->delete('tpedu_jobs')->condition('uuid', $uuid)->execute();
    $database->delete('tpedu_assignment')->condition('uuid', $uuid)->execute();
    $config = \Drupal::config('tpedu.settings');
    $user = api('one_user', ['uuid' => $uuid]);
    if ($user) {
        if (is_array($user->uid)) {
            foreach ($user->uid as $u) {
                if (!strpos($u, '@') && !is_phone($u)) {
                    $account = $u;
                }
            }
        } else {
            $account = $user->uid;
        }
        $stu = ($user->employeeType == '學生') ? 1 : 0;
        $m_dept_id = '';
        $m_dept_name = '';
        $m_role_id = '';
        $m_role_name = '';
        if ($stu == 1) {
            $myclass = $user->tpClass;
            $myseat = $user->tpSeat;
            $m_dept_id = $myclass;
            $m_dept_name = $myclass;
            if (isset($user->tpClassTitle)) {
                $m_dept_name = $user->tpClassTitle;
            }
            $m_role_id = $m_dept_id;
            $m_role_name = $m_dept_name;
        } else {
            if (isset($user->ou) && isset($user->title)) {
                $sdept = $config->get('sub_dept');
                $keywords = explode(',', $sdept);
                if (is_array($user->ou)) {
                    foreach ($user->ou as $ou_pair) {
                        $a = explode(',', $ou_pair);
                        $o = $a[0];
                        $dept_name = get_unit_name($a[1]);
                        $ckf = 0;
                        foreach ($keywords as $k) {
                            if (!(strpos($dept_name, $k) === false)) {
                                $ckf = 1;
                            }
                        }
                        if (!$ckf || ($m_dept_id == '' && $ckf)) {
                            $m_dept_id = $a[1];
                            $m_dept_name = $dept_name;
                        }
                    }
                } else {
                    $a = explode(',', $user->ou);
                    $o = $a[0];
                    $m_dept_id = $a[1];
                    $d = $user->department->{$o}[0];
                    $m_dept_name = $d->name;
                }
                if (is_array($user->title)) {
                    foreach ($user->title as $ro_pair) {
                        $a = explode(',', $ro_pair);
                        $o = $a[0];
                        $role_name = get_role_name($a[2]);
                        $ckf = 0;
                        foreach ($keywords as $k) {
                            if (!(strpos($role_name, $k) === false)) {
                                $ckf = 1;
                            }
                        }
                        if (!$ckf || ($m_dept_id == '' && $ckf)) {
                            $m_role_id = $a[2];
                            $m_role_name = $role_name;
                        }
                        $database->insert('tpedu_jobs')->fields([
                            'uuid' => $uuid,
                            'dept_id' => $a[1],
                            'role_id' => $a[2],
                        ])->execute();
                    }
                } else {
                    $a = explode(',', $user->title);
                    $o = $a[0];
                    $m_role_id = $a[1];
                    $d = $user->titleName->$o[0];
                    $m_role_name = $d->name;
                    $database->insert('tpedu_jobs')->fields([
                        'uuid' => $uuid,
                        'dept_id' => $a[1],
                        'role_id' => $a[2],
                    ])->execute();
                }
            }
            if (!empty($user->tpTutorClass)) {
                $myclass = $user->tpTutorClass;
            }
            if (isset($user->tpTeachClass)) {
                foreach ($user->tpTeachClass as $assign_pair) {
                    $a = explode(',', $assign_pair);
                    $database->insert('tpedu_assignment')->fields([
                        'uuid' => $uuid,
                        'class_id' => $a[1],
                        'subject_id' => $a[2],
                    ])->execute();
                }
            }
        }
        $fields = [
            'uuid' => $uuid,
            'idno' => $user->cn,
            'id' => $user->employeeNumber,
            'student' => $stu,
            'account' => $account,
            'sn' => $user->sn,
            'gn' => $user->givenName,
            'realname' => $user->displayName,
            'dept_id' => $m_dept_id,
            'dept_name' => $m_dept_name,
            'role_id' => $m_role_id,
            'role_name' => $m_role_name,
            'birthdate' => date('Y-m-d H:i:s', strtotime($user->birthDate)),
            'gender' => $user->gender,
            'status' => $user->inetUserStatus,
            'fetch_date' => date('Y-m-d H:i:s'),
        ];
        if (!empty($user->mobile)) {
            $fields['mobile'] = $user->mobile;
        }
        if (!empty($user->telephoneNumber)) {
            $fields['telephone'] = $user->telephoneNumber;
        }
        if (!empty($user->homePhone)) {
            $fields['telephone'] = $user->homePhone;
        }
        if (!empty($user->registeredAddress)) {
            $fields['address'] = $user->registeredAddress;
        }
        if (!empty($user->homePostalAddress)) {
            $fields['address'] = $user->homePostalAddress;
        }
        if (!empty($user->mail)) {
            $fields['email'] = preg_replace('/\s(?=)/', '', $user->mail);
        }
        if (!empty($user->wWWHomePage)) {
            $fields['www'] = $user->wWWHomePage;
        }
        if (!empty($myclass)) {
            $fields['class'] = $myclass;
        }
        if (!empty($myseat)) {
            $fields['seat'] = $myseat;
        }
        if (!empty($user->tpCharacter)) {
            if (is_array($user->tpCharacter)) {
                $fields['character'] = implode(',', $user->tpCharacter);
            } else {
                $fields['character'] = $user->tpCharacter;
            }
        }
        $database->insert('tpedu_people')->fields($fields)->execute();
    }
}

function alle_teacher_id($idno)
{
    $teachers = alle('all_teachers');
    if ($teachers && is_array($teachers)) {
        foreach ($teachers as $teacher) {
            $teaid = $teacher->teaid;
            $userdata = alle('teacher_profile', ['teaid' => $teaid]);
            if ($userdata[0]->idno == $idno) {
                return $teaid;
            }
        }
    }
}

function alle_student_id($idno, $cls)
{
    $students = alle('students_of_class', ['cls' => $cls]);
    foreach ($students->students as $stdno) {
        $userdata = alle('student_profile', ['stdno' => $stdno]);
        if ($userdata[0]->idno == $idno) {
            return $stdno;
        }
    }
}

function alle_fetch_user($uuid)
{
    $database = \Drupal::database();
    $config = \Drupal::config('tpedu.settings');
    $user = api('one_user', ['uuid' => $uuid]);
    if ($user) {
        $account = '';
        if (is_array($user->uid)) {
            foreach ($user->uid as $u) {
                if (!strpos($u, '@') && !is_phone($u)) {
                    $account = $u;
                }
            }
        } else {
            $account = $user->uid;
        }
        $stu = ($user->employeeType == '學生') ? 1 : 0;
        $myid = $user->employeeNumber;
        if (!$myid) {
            $olddata = $database->query("select id from tpedu_people where uuid='$uuid'")->fetchField();
            if ($olddata) {
                $myid = $olddata;
            } else {
                if ($stu) {
                    $myid = alle_student_id($user->cn, $user->tpClass);
                } else {
                    $myid = alle_teacher_id($user->cn);
                }
            }
        }
        $m_dept_id = '';
        $m_dept_name = '';
        $m_role_id = '';
        $m_role_name = '';
        if ($stu == 1) {
            $database->delete('tpedu_people')->condition('uuid', $uuid)->execute();
            $myclass = $user->tpClass;
            $myseat = $user->tpSeat;
            $m_dept_id = $myclass;
            $m_dept_name = $myclass;
            if (isset($user->tpClassTitle)) {
                $m_dept_name = $user->tpClassTitle;
            }
            $m_role_id = $m_dept_id;
            $m_role_name = $m_dept_name;
        } else {
            $database->delete('tpedu_people')->condition('uuid', $uuid)->execute();
            $database->delete('tpedu_jobs')->condition('uuid', $uuid)->execute();
            $database->delete('tpedu_assignment')->condition('uuid', $uuid)->execute();
            $userdata = alle('teacher_info', ['teaid' => $myid]);
            if (!empty($userdata[0]->class)) {
                $myclass = $userdata[0]->class;
            }
            if (!empty($userdata[0]->job_title)) {
                $sdept = $config->get('sub_dept');
                $keywords = explode(',', $sdept);
                foreach ($userdata[0]->job_title as $role_name) {
                    $data = $database->query("select * from tpedu_roles where name='$role_name'")->fetchObject();
                    $ckf = 0;
                    foreach ($keywords as $k) {
                        if (!(strpos($role_name, $k) === false)) {
                            $ckf = 1;
                        }
                    }
                    if (!$ckf || ($m_dept_id == '' && $ckf)) {
                        $m_dept_id = $data->unit;
                        $m_dept_name = get_unit_name($data->unit);
                        $m_role_id = $data->id;
                        $m_role_name = $data->name;
                    }
                    $database->insert('tpedu_jobs')->fields([
                        'uuid' => $uuid,
                        'dept_id' => $data->unit,
                        'role_id' => $data->id,
                    ])->execute();
                }
            }
            if (!empty($userdata[0]->subjects)) {
                $subjects = $userdata[0]->subjects;
                $domain = $userdata[0]->domain;
                foreach ($subjects as $n => $subj_name) {
                    $subj_id = $database->query("select id from tpedu_subjects where name='$subj_name'")->fetchField();
                    if (!$subj_id) {
                        $maxid = $database->query('select max(id) from tpedu_subjects')->fetchField();
                        if (empty($maxid)) {
                            $snid = 'subj01';
                        } else {
                            $snid = intval(substr($maxid, 4)) + 1;
                            if ($snid < 10) {
                                $snid = "0$snid";
                            }
                            $snid = "subj$snid";
                        }
                        if (count($subjects) == count($domain)) {
                            $database->insert('tpedu_subjects')->fields([
                                'id' => $snid,
                                'domain' => $domain[$n],
                                'name' => $subj_name,
                            ])->execute();
                        } else {
                            $database->insert('tpedu_subjects')->fields([
                                'id' => $snid,
                                'domain' => '108',
                                'name' => $subj_name,
                            ])->execute();
                        }
                    }
                }
            }

            $userdata = alle('teacher_subjects', ['teaid' => $myid]);
            if (!empty($userdata[0]->classes)) {
                foreach ($userdata[0]->classes as $cls_data) {
                    $cls = $cls_data->id;
                    foreach ($cls_data->subjects as $subj) {
                        $database->insert('tpedu_assignment')->fields([
                            'uuid' => $uuid,
                            'class_id' => $cls,
                            'subject_id' => key((array) $subj),
                        ])->execute();
                    }
                }
            }
        }
        $fields = [
            'uuid' => $uuid,
            'idno' => $user->cn,
            'id' => $myid,
            'student' => $stu,
            'account' => $account,
            'sn' => $user->sn,
            'gn' => $user->givenName,
            'realname' => $user->displayName,
            'dept_id' => $m_dept_id,
            'dept_name' => $m_dept_name,
            'role_id' => $m_role_id,
            'role_name' => $m_role_name,
            'birthdate' => date('Y-m-d H:i:s', strtotime($user->birthDate)),
            'gender' => $user->gender,
            'status' => $user->inetUserStatus,
            'fetch_date' => date('Y-m-d H:i:s'),
        ];
        if (!empty($user->mobile)) {
            $fields['mobile'] = $user->mobile;
        }
        if (!empty($user->telephoneNumber)) {
            $fields['telephone'] = $user->telephoneNumber;
        }
        if (!empty($user->homePhone)) {
            $fields['telephone'] = $user->homePhone;
        }
        if (!empty($user->registeredAddress)) {
            $fields['address'] = $user->registeredAddress;
        }
        if (!empty($user->homePostalAddress)) {
            $fields['address'] = $user->homePostalAddress;
        }
        if (!empty($user->mail)) {
            $fields['email'] = preg_replace('/\s(?=)/', '', $user->mail);
        }
        if (!empty($user->wWWHomePage)) {
            $fields['www'] = $user->wWWHomePage;
        }
        if (!empty($myclass)) {
            $fields['class'] = $myclass;
        }
        if (!empty($myseat)) {
            $fields['seat'] = $myseat;
        }
        if (!empty($user->tpCharacter)) {
            if (is_array($user->tpCharacter)) {
                $fields['character'] = implode(',', $user->tpCharacter);
            } else {
                $fields['character'] = $user->tpCharacter;
            }
        }
        $database->insert('tpedu_people')->fields($fields)->execute();
    }
}

function alle_sync_teachers()
{
    $uuids = api('all_teachers');
    if ($uuids && is_array($uuids)) {
        foreach ($uuids as $uuid) {
            alle_fetch_user($uuid);
        }
    }
}

function alle_sync_students($cls)
{
    $uuids = api('students_of_class', ['cls' => $cls]);
    if ($uuids && is_array($uuids)) {
        foreach ($uuids as $uuid) {
            alle_fetch_user($uuid);
        }
    }
}

function get_user($uuid)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if (is_numeric($uuid)) {
        $data = \Drupal::database()->query("select a.uuid from users a join users_field_data b on a.uid=b.uid where b.init='tpedu' and a.uid='$uuid'")->fetchObject();
        if (!$data) {
            return false;
        }
        $uuid = $data->uuid;
        $query = \Drupal::database()
            ->query("select * from {tpedu_people} where uuid='$uuid' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_people} where uuid='$uuid' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    }
    $data = $query->fetchObject();
    if (!$data) {
        if (empty($config->get('alle_project'))) {
            fetch_user($uuid);
        } else {
            alle_fetch_user($uuid);
        }
        $query = \Drupal::database()->query("select * from {tpedu_people} where uuid='$uuid'");
        $data = $query->fetchObject();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_user_name($uid)
{
    $config = \Drupal::config('tpedu.settings');
    $data = \Drupal::database()->query("select * from users where init='tpedu' and uid='$uid'")->fetchObject();
    if ($data) {
        $query = \Drupal::database()->query("select realname from {tpedu_people} where uuid='$data->uuid'");
        $data = $query->fetchColumn(0);
        if ($data) {
            return $data;
        }
    }

    return $uid;
}

function find_user(array $filter)
{
    if (empty($filter)) {
        return false;
    }
    $uuids = api('find_users', $filter);
    if ($uuids && is_array($uuids)) {
        $users = [];
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function ($a, $b) { return strcmp($a->realname, $b->realname); });

        return $users;
    }

    return false;
}

function all_teachers()
{
    $uuids = api('all_teachers');
    if ($uuids && is_array($uuids)) {
        $users = [];
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function ($a, $b) { return strcmp($a->realname, $b->realname); });

        return $users;
    }

    return false;
}

function fetch_units()
{
    if (\Drupal::config('tpedu.settings')->get('alle_project')) {
        return;
    }
    \Drupal::database()->delete('tpedu_units')->execute();
    $ous = api('all_units');
    if ($ous) {
        foreach ($ous as $o) {
            if (mb_strpos($o->description, '學校教師') !== false) {
                $config = \Drupal::configFactory()->getEditable('tpedu.settings');
                $config->set('sub_dept', $o->ou);
                $config->save();
            }
            $fields = [
                'id' => $o->ou,
                'name' => $o->description,
                'fetch_date' => date('Y-m-d H:i:s'),
            ];
            \Drupal::database()->insert('tpedu_units')->fields($fields)->execute();
        }
    }
}

function all_units()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if ($config->get('alle_project')) {
        $query = \Drupal::database()
            ->query('select * from {tpedu_units} order by id');
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_units} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    }
    $data = $query->fetchAll();
    if (!$data) {
        fetch_units();
        $query = \Drupal::database()->query('select * from {tpedu_units} order by id');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_unit($ou)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if ($config->get('alle_project')) {
        $query = \Drupal::database()
            ->query("select * from {tpedu_units} where id='$ou'");
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_units} where id='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    }
    $data = $query->fetchObject();
    if (!$data) {
        fetch_units();
        $query = \Drupal::database()->query("select * from {tpedu_units} where id='$ou'");
        $data = $query->fetchObject();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_unit_name($ou)
{
    $unit = get_unit($ou);

    return $unit->name;
}

function get_units_of_job($uuid)
{
    $query = \Drupal::database()->query("select dept_id from {tpedu_jobs} where uuid='$uuid'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select dept_id from {tpedu_jobs} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $units = [];
        foreach ($data as $job) {
            $units[] = get_unit($job->dept_id);
        }

        return $units;
    }

    return false;
}

function get_teachers_of_unit($ou)
{
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_jobs} where dept_id='$ou'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_jobs} where dept_id='$ou'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $job) {
            $users[] = get_user($job->uuid);
        }

        return $users;
    }

    return false;
}

function fetch_roles()
{
    if (\Drupal::config('tpedu.settings')->get('alle_project')) {
        return;
    }
    \Drupal::database()->delete('tpedu_roles')->execute();
    $ous = api('all_units');
    if ($ous) {
        foreach ($ous as $o) {
            $roles = api('roles_of_unit', ['ou' => $o->ou]);
            if ($roles) {
                foreach ($roles as $r) {
                    $query = \Drupal::database()
                        ->query("select * from {tpedu_roles} where id = '$r->cn'");
                    $data = $query->fetchAll();
                    if (!$data) {
                        $fields = [
                            'id' => $r->cn,
                            'unit' => $o->ou,
                            'name' => $r->description,
                            'fetch_date' => date('Y-m-d H:i:s'),
                        ];
                        \Drupal::database()->insert('tpedu_roles')->fields($fields)->execute();
                    }
                }
            }
        }
    }
}

function all_roles()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if ($config->get('alle_project')) {
        $query = \Drupal::database()
            ->query('select * from {tpedu_roles} order by id');
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_roles} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    }
    $data = $query->fetchAll();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query('select * from {tpedu_roles} order by id');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_roles_of_unit($ou)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if ($config->get('alle_project')) {
        $query = \Drupal::database()
            ->query("select * from {tpedu_roles} where unit='$ou'");
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_roles} where unit='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    }
    $data = $query->fetchAll();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_roles} where unit='$ou'");
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_role($ro)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    if ($config->get('alle_project')) {
        $query = \Drupal::database()
            ->query("select * from {tpedu_roles} where id='$ro'");
    } else {
        $query = \Drupal::database()
            ->query("select * from {tpedu_roles} where id='$ro' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    }
    $data = $query->fetchObject();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_roles} where id='$ro'");
        $data = $query->fetchObject();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_role_name($ro)
{
    $role = get_role($ro);

    return $role->name;
}

function get_teachers_of_role($ro)
{
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_jobs} where role_id='$ro'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_jobs} where role_id='$ro'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $job) {
            $users[] = get_user($job->uuid);
        }

        return $data;
    }

    return false;
}

function fetch_subjects()
{
    if (\Drupal::config('tpedu.settings')->get('alle_project')) {
        return;
    }
    \Drupal::database()->delete('tpedu_subjects')->execute();
    $subjects = api('all_subjects');
    if ($subjects) {
        foreach ($subjects as $s) {
            $fields = [
                'id' => $s->tpSubject,
                'domain' => $s->tpSubjectDomain,
                'name' => $s->description,
                'fetch_date' => date('Y-m-d H:i:s'),
            ];
            \Drupal::database()->insert('tpedu_subjects')->fields($fields)->execute();
        }
    }
}

function all_subjects()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query('select * from {tpedu_subjects} order by id');
    $data = $query->fetchAll();
    if (!$data) {
        fetch_subjects();
        $query = \Drupal::database()->query('select * from {tpedu_subjects} order by id');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function all_domains()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query('select distinct domain from {tpedu_subjects}');
    $data = $query->fetchAll();
    if (!$data) {
        fetch_subjects();
        $query = \Drupal::database()->query('select distinct domain from {tpedu_subjects}');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_subjects_of_domain($domain)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_subjects} where domain='$domain'");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_subjects} where domain='$domain'");
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_teachers_of_domain($dom)
{
    $subjects = get_subjects_of_domain($dom);
    if ($subjects) {
        foreach ($subjects as $s) {
            $subs[] = "'$s->id'";
        }
    }
    $sub_list = implode(',', $subs);
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_assignment} where subject_id in ($sub_list)");

    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_assignment} where subject_id in ($sub_list)");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }

        return $users;
    }

    return false;
}

function get_subjects_of_assignment($uuid)
{
    $query = \Drupal::database()->query("select * from {tpedu_assignment} where uuid='$uuid'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select * from {tpedu_assignment} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = [];
        foreach ($data as $assign) {
            $subjects[] = get_subject($assign->subject_id);
        }

        return $subjects;
    }

    return false;
}

function get_subject($sub)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_subjects} where id='$sub'");
    $data = $query->fetchObject();
    if (!$data) {
        fetch_subjects();
        $query = \Drupal::database()->query("select * from {tpedu_subjects} where id='$sub'");
        $data = $query->fetchObject();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_teachers_of_subject($sub)
{
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_assignment} where subject_id='$sub'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_assignment} where subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }

        return $users;
    }

    return false;
}

function get_classes_of_subject($sub)
{
    $query = \Drupal::database()
        ->query("select class_id from {tpedu_assignment} where subject_id='$sub'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select class_id from {tpedu_assignment} where subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = [];
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }

        return $classes;
    }

    return false;
}

function fetch_classes()
{
    \Drupal::database()->delete('tpedu_classes')->execute();
    $classes = api('all_classes');
    if ($classes) {
        foreach ($classes as $c) {
            $fields = [
                'id' => $c->ou,
                'grade' => $c->grade,
                'name' => $c->description,
                'fetch_date' => date('Y-m-d H:i:s'),
            ];
            if (isset($c->tutor[0])) {
                $fields['tutor'] = $c->tutor[0];
            }
            \Drupal::database()->insert('tpedu_classes')->fields($fields)->execute();
        }
    }
}

function all_grade()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct grade from {tpedu_classes} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by grade");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_classes();
        $query = \Drupal::database()->query('select distinct grade from {tpedu_classes} order by grade');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function all_classes()
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_classes} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_classes();
        $query = \Drupal::database()->query('select * from {tpedu_classes} order by id');
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_classes_of_grade($grade)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_classes} where grade='$grade' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_classes();
        $query = \Drupal::database()->query("select * from {tpedu_classes} where grade='$grade' order by id");
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_teachers_of_grade($grade)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_people} where student=0 and class like '$grade%' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by class");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select * from {tpedu_people} where student=0 and class like '$grade%' order by class");
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function fetch_class($ou)
{
    \Drupal::database()->delete('tpedu_classes')->condition('id', $ou)->execute();
    $c = api('one_class', ['cls' => $ou]);
    if ($c) {
        $fields = [
            'id' => $c->ou,
            'grade' => $c->grade,
            'name' => $c->description,
            'fetch_date' => date('Y-m-d H:i:s'),
        ];
        if (isset($c->tutor)) {
            $fields['tutor'] = $c->tutor;
        }
        \Drupal::database()->insert('tpedu_classes')->fields($fields)->execute();
    }
}

function one_class($ou)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_classes} where id='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)")
        ;
    $data = $query->fetchObject();
    if (!$data) {
        fetch_class($ou);
        $query = \Drupal::database()->query("select * from {tpedu_classes} where id='$ou'");
        $data = $query->fetchObject();
    }
    if ($data) {
        return $data;
    }

    return false;
}

function get_class_name($ou)
{
    $class = one_class($ou);
    if ($class) {
        return $class->name;
    } else {
        return false;
    }
}

function get_teachers_of_class($cls)
{
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_assignment} where class_id='$cls'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_assignment} where class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }

        return $users;
    }

    return false;
}

function get_students_of_class($cls)
{
    $uuids = api('students_of_class', ['cls' => $cls]);
    if ($uuids && is_array($uuids)) {
        $users = [];
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function ($a, $b) { return strcmp($a->realname, $b->realname); });

        return $users;
    }

    return false;
}

function get_subjects_of_class($cls)
{
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where class_id='$cls'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = [];
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }

        return $subjects;
    }

    return false;
}

function get_jobs($uuid)
{
    $query = \Drupal::database()->query("select * from {tpedu_jobs} where uuid='$uuid'");
    $jobs = $query->fetchAll();
    if ($jobs) {
        foreach ($jobs as $j) {
            $unit = get_unit($j->dept_id);
            $role = get_role($j->role_id);
            $j->dept_name = $unit->name;
            $j->role_name = $role->name;
        }

        return $jobs;
    }

    return false;
}

function get_teach_classes($uuid)
{
    $query = \Drupal::database()
        ->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = [];
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }

        return $classes;
    }

    return false;
}

function get_teach_subjects($uuid)
{
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = [];
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }

        return $subjects;
    }

    return false;
}

function get_teach_classes_of_subject($uuid, $sub)
{
    $query = \Drupal::database()
        ->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid' and subject_id='$sub'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid' and subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = [];
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }

        return $classes;
    }

    return false;
}

function get_teach_subjects_of_class($uuid, $cls)
{
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid' and class_id='$cls'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid' and class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = [];
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }

        return $subjects;
    }

    return false;
}

function get_teachers_by_assign($cls, $sub)
{
    $query = \Drupal::database()
        ->query("select distinct uuid from {tpedu_assignment} where class_id='$cls' and subject_id='$sub'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct uuid from {tpedu_assignment} where class_id='$cls' and subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = [];
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }

        return $users;
    }

    return false;
}

function get_assign_by_domain($cls, $dom)
{
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_assignment} a join {tpedu_subjects} b on a.subject_id=b.id where a.class_id='$cls' and b.domain='$dom'");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select * from {tpedu_assignment} a join {tpedu_subjects} b on a.subject_id=b.id where a.class_id='$cls' and b.domain='$dom'");
        $data = $query->fetchAll();
    }
    if ($data) {
        return $data;
    }

    return false;
}
