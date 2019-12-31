<?php

function current_seme() {
    if (date('m') > 7) {
      $year = date('Y') - 1911;
      $seme = 1;
    }
    elseif (date('m') < 2) {
      $year = date('Y') - 1912;
      $seme = 1;
    }
    else {
      $year = date('Y') - 1912;
      $seme = 2;
    }
    return array('year' => $year, 'seme' => $seme);
}

function get_tokens($auth_code) {
    global $base_url;
    $config = \Drupal::config('tpedu.settings');
    $response = \Drupal::httpClient()->post($config->get('api.token'), array(
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
        'form_params' => array(
            'grant_type' => 'authorization_code',
            'client_id' => $config->get('client_id'),
            'client_secret' => $config->get('client_secret'),
            'redirect_uri' => $config->get('call_back'),
            'code' => $auth_code,
        ),
    ));
    $data = json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
        $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
        $tempstore->set('expires_in', time() + $data->expires_in);
        $tempstore->set('access_token', $data->access_token);
        $tempstore->set('refresh_token', $data->refresh_token);
    } else {
        if (isset($json->error)) {
            \Drupal::logger('tpedu')->error('oauth2 response:'. $dataapi .'=>'. $json->error);
            return false;
        }
    }
}

function refresh_tokens() {
    $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
    if ($tempstore->get('refresh_token') && $tempstore->get('expires_in') < time()) {
        $config = \Drupal::config('tpedu.settings');
        $response = \Drupal::httpClient()->post($config->get('api.token'), array(
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'form_params' => array(
                'grant_type' => 'refresh_token',
                'client_id' => $config->get('client_id'),
                'client_secret' => $config->get('client_secret'),
                'refresh_token' => $tempstore->get('refresh_token'),
                'scope' => 'user',
            ),
        ));
        $data = json_decode($response->getBody());
        if ($response->getStatusCode() == 200) {
            $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
            $tempstore->set('expires_in', time() + $data->expires_in);
            $tempstore->set('access_token', $data->access_token);
            $tempstore->set('refresh_token', $data->refresh_token);
        } else {
            if (isset($json->error)) {
                \Drupal::logger('tpedu')->error('oauth2 response:'. $dataapi .'=>'. $json->error);
                return false;
            }
        }
    }
}

function who() {
    $config = \Drupal::config('tpedu.settings');
    $tempstore = \Drupal::service('user.private_tempstore')->get('tpedu');
    if ($tempstore->get('access_token')) {
        $response = \Drupal::httpClient()->get($config->get('api.login'), array(
            'headers' => array( 'Authorization' => 'Bearer ' . $tempstore->get('access_token') ),
        ));
        $user = json_decode($response->getBody());
        if ($response->getStatusCode() == 200) {
            return $user->uuid;    
        } else {
            if (isset($json->error)) {
                \Drupal::logger('tpedu')->error('oauth2 response:'. $dataapi .'=>'. $json->error);
                return false;
            }
        }
    }
    return false;
}

function api($which, array $replacement = null) {
    $config = \Drupal::config('tpedu.settings');
    $dataapi = $config->get('api.' . $which);
    if ($which == 'find_users') {
        if (!empty($replacement)) {
            $dataapi .= '?';
            foreach ($replacement as $key => $data) {
                $dataapi .= $key . '=' . $data . '&';
            }
            $dataapi = substr($dataapi, 0, -1);
        }
    } else {
        $replacement['dc'] = $config->get('api.dc');
        $search = array();
        $values = array();
        foreach ($replacement as $key => $data) {
            $search[] = '{'.$key.'}';
            $values[] = $data;
        }
        $dataapi = str_replace($search, $values, $dataapi);
    }
    $response = \Drupal::httpClient()->get($dataapi , [
        'headers' => [ 'Authorization' => 'Bearer ' . $config->get('admin_token') ],
        'http_errors' => false,
    ]);
    $json = json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
        return $json;    
    } else {
        if (isset($json->error)) {
            \Drupal::logger('tpedu')->error('oauth2 response:'. $dataapi .'=>'. $json->error);
            return false;
        }
    }
}

function profile() {
    $user = api('profile');
    if (!empty($user)) return $user;
    return false;
}

function fetch_user($uuid) {
    $database = \Drupal::database();
    $database->delete('tpedu_people')->condition('uuid', $uuid)->execute();
    $database->delete('tpedu_jobs')->condition('uuid', $uuid)->execute();
    $database->delete('tpedu_assignment')->condition('uuid', $uuid)->execute();
    $config = \Drupal::config('tpedu.settings');
    $user = api('one_user', array( 'uuid' => $uuid ));
    if (!isset($user->error)) {
        if (is_array($user->uid)) {
            foreach ($user->uid as $u) {
                if (!strpos($u, '@') && !is_numeric($u)) $account = $u; 
            }
        } else {
            $account = $user->uid;
        }
        $stu = ($user->employeeType == '學生') ? 1 : 0;
        if ($stu == 1) {
            $myclass = $user->tpClass;
            $myseat = $user->tpSeat;
            $m_dept_id = $myclass;
            $m_dept_name = $myclass;
            if (isset($user->tpClassTitle)) $m_dept_name = $user->tpClassTitle;
            $m_role_id = $m_dept_id;
            $m_role_name = $m_dept_name;
        } else {
            $sdept = $config->get('sub_dept');
            if (is_array($user->ou)) {
                foreach ($user->ou as $ou_pair) {
                    $a = explode(',', $ou_pair);
                    $o = $a[0];
                    if ($a[1] != $sdept) {
                        $m_dept_id = $a[1];
                        $depts = $user->department->$o;
                        foreach ($depts as $d) {
                            if ($d->key == $ou_pair) $m_dept_name = $d->name;
                        }
                    } else {
                        $s_dept_id = $a[1];
                        $depts = $user->department->$o;
                        foreach ($depts as $d) {
                            if ($d->key == $ou_pair) $s_dept_name = $d->name;
                        }
                    }
                }
                if (empty($m_dept_id)) {
                    $m_dept_id = $s_dept_id;
                    $m_dept_name = $s_dept_name;
                }
            } else {
                $a = explode(',', $user->ou);
                $o = $a[0];
                $m_dept_id = $a[1];
                $d = $user->department->$o[0];
                $m_dept_name = $d->name;
            }
            if (is_array($user->title)) {
                foreach ($user->title as $ro_pair) {
                    $a = explode(',', $ro_pair);
                    $o = $a[0];
                    if ($a[1] == $m_dept_id) {
                        $m_role_id = $a[2];
                        $roles = $user->titleName->$o;
                        foreach ($roles as $r) {
                            if ($r->key == $ro_pair) $m_role_name = $r->name;
                        }
                    }
                    $database->insert('tpedu_jobs')->fields(array(
                        'uuid' => $uuid,
                        'dept_id' => $a[1],
                        'role_id' => $a[2],
                    ))->execute();
                }
            } else {
                $a = explode(',', $user->title);
                $o = $a[0];
                $m_role_id = $a[1];
                $d = $user->titleName->$o[0];
                $m_role_name = $d->name;
                $database->insert('tpedu_jobs')->fields(array(
                    'uuid' => $uuid,
                    'dept_id' => $a[1],
                    'role_id' => $a[2],
                ))->execute();
            }
            if (!empty($user->tpTutorClass)) $myclass = $user->tpTutorClass;
            if (isset($user->tpTeachClass)) {
                foreach ($user->tpTeachClass as $assign_pair) {
                    $a = explode(',', $assign_pair);
                    $database->insert('tpedu_assignment')->fields(array(
                        'uuid' => $uuid,
                        'class_id' => $a[1],
                        'subject_id' => $a[2],
                    ))->execute();
                }
            }
        }
        $fields = array(
            'uuid' => $uuid,
            'idno' => $user->cn,
            'id' => $user->employeeNumber,
            'student' => $stu,
            'account' => $account,
            'realname' => $user->displayName,
            'dept_id' => $m_dept_id,
            'dept_name' => $m_dept_name,
            'role_id' => $m_role_id,
            'role_name' => $m_role_name,
            'birthdate' => date('Y-m-d H:i:s', strtotime($user->birthDate)),
            'gender' => $user->gender,
        );
        if (!empty($user->mobile)) $fields['mobile'] = $user->mobile;
        if (!empty($user->telephoneNumber)) $fields['telephone'] = $user->telephoneNumber;
        if (!empty($user->homePhone)) $fields['telephone'] = $user->homePhone;
        if (!empty($user->registeredAddress)) $fields['address'] = $user->registeredAddress;
        if (!empty($user->homePostalAddress)) $fields['address'] = $user->homePostalAddress;
        if (!empty($user->mail)) $fields['email'] = $user->mail;
        if (!empty($user->wWWHomePage)) $fields['www'] = $user->wWWHomePage;
        if (!empty($myclass)) $fields['class'] = $myclass;
        if (!empty($myseat)) $fields['seat'] = $myseat;
        if (!empty($user->tpCharacter)) $fields['character'] = $user->tpCharacter;
        $database->insert('tpedu_people')->fields($fields)->execute();
    }
}

function get_user($uuid) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_people} where uuid='$uuid' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchObject();
    if (!$data) {
        fetch_user($uuid);
        $query = \Drupal::database()->query("select * from {tpedu_people} where uuid='$uuid'");
        $data = $query->fetchObject();
    }
    if ($data) return $data;
    return false;
}

function find_user(array $filter) {
    if (empty($filter)) return false;
    $uuids = api('find_users', $filter);
    if ($uuids && is_array($uuids)) {
        $users = array();
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function($a, $b) { return strcmp($a->name, $b->name); });
        return $users;
    }
    return false;
}

function all_teachers() {
    $uuids = api('all_teachers');
    if ($uuids && is_array($uuids)) {
        $users = array();
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function($a, $b) { return strcmp($a->name, $b->name); });
        return $users;
    }
    return false;
}

function fetch_units() {
    \Drupal::database()->delete('tpedu_units')->execute();
    $ous = api('all_units');
    if ($ous) {
        foreach ($ous as $o) {
            if (strpos($o->description, '科任')) {
                $config = \Drupal::configFactory()->getEditable('tpedu.settings');
                $config->set('sub_dept', $o->ou);
                $config->save();
            }
            $fields = array(
                'id' => $o->ou,
                'name' => $o->description,
            );
            \Drupal::database()->insert('tpedu_units')->fields($fields)->execute();
        }
    }
}  

function all_units() {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_units} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_units();
        $query = \Drupal::database()->query('select * from {tpedu_units} order by id');
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}

function get_unit($ou) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_units} where id='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchObject();
    if (!$data) {
        fetch_units();
        $query = \Drupal::database()->query("select * from {tpedu_units} where id='$ou'");
        $data = $query->fetchObject();
    }
    if ($data) return $data;
    return false;
}

function get_teachers_of_unit($ou) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_jobs} where dept_id='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_jobs} where dept_id='$ou'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = array();
        foreach ($data as $job) {
            $users[] = get_user($job->uuid);
        }
        return $users;
    }
    return false;
}

function fetch_roles() {
    \Drupal::database()->delete('tpedu_roles')->execute();
    $ous = api('all_units');
    if ($ous) {
        foreach ($ous as $o) {
            $roles = api('roles_of_unit', array( 'ou' => $o->ou));
            foreach ($roles as $r) {
                $fields = array(
                    'id' => $r->cn,
                    'unit' => $o->ou,
                    'name' => $r->description,
                );
                \Drupal::database()->insert('tpedu_roles')->fields($fields)->execute();
            }
        }
    }
}  

function all_roles() {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_roles} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_roles} order by id");
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}

function get_roles_of_unit($ou) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_roles} where unit='$ou' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_roles} where unit='$ou'");
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}

function get_role($ro) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_roles} where id='$ro' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchObject();
    if (!$data) {
        fetch_roles();
        $query = \Drupal::database()->query("select * from {tpedu_roles} where id='$ro'");
        $data = $query->fetchObject();
    }
    if ($data) return $data;
    return false;
}

function get_teachers_of_role($ro) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_jobs} where role_id='$ro' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_jobs} where role_id='$ro'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = array();
        foreach ($data as $job) {
            $users[] = get_user($job->uuid);
        }
        return $data;
    }
    return false;
}

function fetch_subjects() {
    \Drupal::database()->delete('tpedu_subjects')->execute();
    $subjects = api('all_subjects');
    if ($subjects) {
        foreach ($subjects as $s) {
            $fields = array(
                'id' => $s->tpSubject,
                'domain' => $s->tpSubjectDomain,
                'name' => $s->description,
            );
            \Drupal::database()->insert('tpedu_subjects')->fields($fields)->execute();
        }
    }
}  

function all_subjects() {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_subjects} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_subjects();
        $query = \Drupal::database()->query("select * from {tpedu_subjects} order by id");
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}

function fetch_subject($sub) {
    \Drupal::database()->delete('tpedu_subjects')->condition('id', $sub)->execute();
    $s = api('one_subject', array('sub' => $sub));
    if ($s) {
        $fields = array(
            'id' => $s->tpSubject,
            'domain' => $s->tpSubjectDomain,
            'name' => $s->description,
    );
    \Drupal::database()->insert('tpedu_subjects')->fields($fields)->execute();
    }
}  

function get_subject($sub) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_subjects} where id='$sub' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchObject();
    if (!$data) {
        fetch_subject($sub);
        $query = \Drupal::database()->query("select * from {tpedu_subjects} where id='$sub'");
        $data = $query->fetchObject();
    }
    if ($data) return $data;
    return false;
}

function get_teachers_of_subject($sub) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_assignment} where subject_id='$sub' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_assignment} where subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = array();
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }
        return $users;
    }
    return false;
}

function get_classes_of_subject($sub) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select class_id from {tpedu_assignment} where subject_id='$sub' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select class_id from {tpedu_assignment} where subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = array();
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }
        return $classes;
    }
    return false;
}

function fetch_classes() {
    \Drupal::database()->delete('tpedu_classes')->execute();
    $classes = api('all_classes');
    if ($classes) {
        foreach ($classes as $c) {
            $fields = array(
                'id' => $c->ou,
                'grade' => $c->grade,
                'name' => $c->description,
            );
            if (isset($c->tutor[0])) $fields['tutor'] = $c->tutor[0];
            \Drupal::database()->insert('tpedu_classes')->fields($fields)->execute();
        }
    }
}  

function all_classes() {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_classes} where fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY) order by id");
    $data = $query->fetchAll();
    if (!$data) {
        fetch_classes();
        $query = \Drupal::database()->query("select * from {tpedu_classes} order by id");
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}

function get_classes_of_grade($grade) {
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
    if ($data) return $data;
    return false;
}

function fetch_class($ou) {
    \Drupal::database()->delete('tpedu_classes')->condition('id', $ou)->execute();
    $c = api('one_class', array('cls' => $ou));
    if ($c) {
        $fields = array(
            'id' => $c->ou,
            'grade' => $c->grade,
            'name' => $c->description,
        );
        if (isset($c->tutor)) $fields['tutor'] = $c->tutor;
        \Drupal::database()->insert('tpedu_classes')->fields($fields)->execute();
    }
}  

function one_class($ou) {
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
    if ($data) return $data;
    return false;
}

function get_teachers_of_class($cls) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select uuid from {tpedu_assignment} where class_id='$cls' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select uuid from {tpedu_assignment} where class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = array();
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }
        return $users;
    }
    return false;
}

function get_students_of_class($cls) {
    $uuids = api('students_of_class', array( 'cls' => $cls ));
    if ($uuids && is_array($uuids)) {
        $users = array();
        foreach ($uuids as $uuid) {
            $users[] = get_user($uuid);
        }
        usort($users, function($a, $b) { return strcmp($a->name, $b->name); });
        return $users;
    }
    return false;
}

function get_subjects_of_class($cls) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where class_id='$cls' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = array();
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }
        return $subjects;
    }
    return false;
}

function get_teach_classes($uuid) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = array();
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }
        return $classes;
    }
    return false;
}

function get_teach_subjects($uuid) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = array();
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }
        return $subjects;
    }
    return false;
}

function get_teach_classes_of_subject($uuid, $sub) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid' and subject_id='$sub' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct class_id from {tpedu_assignment} where uuid='$uuid' and subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $classes = array();
        foreach ($data as $c) {
            $classes[] = one_class($c->class_id);
        }
        return $classes;
    }
    return false;
}

function get_teach_subjects_of_class($uuid, $cls) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid' and class_id='$cls' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct subject_id from {tpedu_assignment} where uuid='$uuid' and class_id='$cls'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $subjects = array();
        foreach ($data as $s) {
            $subjects[] = get_subject($s->subject_id);
        }
        return $subjects;
    }
    return false;
}

function get_teachers_by_assign($cls, $sub) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select distinct uuid from {tpedu_assignment} where class_id='$cls' and subject_id='$sub' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select distinct uuid from {tpedu_assignment} where class_id='$cls' and subject_id='$sub'");
        $data = $query->fetchAll();
    }
    if ($data) {
        $users = array();
        foreach ($data as $assign) {
            $users[] = get_user($assign->uuid);
        }
        return $users;
    }
    return false;
}

function get_assign_by_domain($cls, $dom) {
    $config = \Drupal::config('tpedu.settings');
    $off = $config->get('refresh_days');
    $query = \Drupal::database()
        ->query("select * from {tpedu_assignment} a join {tpedu_subjects} b on a.subject_id=b.id where a.class_id='$cls' and b.domain='$dom' and fetch_date > DATE_SUB(NOW(), INTERVAL $off DAY)");
    $data = $query->fetchAll();
    if (!$data) {
        all_teachers();
        $query = \Drupal::database()->query("select * from {tpedu_assignment} a join {tpedu_subjects} b on a.subject_id=b.id where a.class_id='$cls' and b.domain='$dom'");
        $data = $query->fetchAll();
    }
    if ($data) return $data;
    return false;
}
