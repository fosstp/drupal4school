<?php

namespace Drupal\tpedu\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class tpeduController extends ControllerBase {

    public function handle(Request $request) {
        global $base_url;
        $config = \Drupal::config('tpedu.settings');
        if (!($config->get('enable'))) throw new AccessDeniedHttpException();
        $auth_code = $request->query->get('code');
        $user_email = $request->query->get('user');
        if ($auth_code) {
            get_tokens($auth_code);
            $uuid = who();
            $user = get_user($uuid);
            $account = \Drupal::database()->query("select * from users where uuid='$uuid'")->execute()->fetchObject();
            if (!$account) {
                $new_user = [
                    'uuid' => $uuid,
                    'name' => $user->account,
                    'mail' => $user->email,
                    'init' => 'tpedu',
                    'pass' => substr($user->idno, -6),
                    'status' => 1,
                ];
                $account = User::create($new_user);
                $account->save();
            } else {
                $account = User::load($account->id);
            }
            user_login_finalize($account);
            if (!empty($config->get('login_goto_url')))
                $nextUrl = $config->get('login_goto_url');
            else
                $nextUrl = $base_url;
            $response = new RedirectResponse(Url::fromUri($nextUrl));
            $response->send();
            return new Response();
        } elseif ($user_email) {
            $user = find_user('email=' . $user_email);
            $account = \Drupal::database()->query("select * from users where uuid='$uuid'")->execute()->fetchObject();
            if (!$account) {
                $new_user = [
                    'uuid' => $uuid,
                    'name' => $user->account,
                    'mail' => $user->email,
                    'init' => 'tpedu',
                    'pass' => substr($user->idno, -6),
                    'status' => 1,
                ];
                $account = User::create($new_user);
                $account->save();
            }
            $user = User::load($account->id());
            user_login_finalize($account);
            if (!empty($config->get('login_goto_url')))
                $nextUrl = $config->get('login_goto_url');
            else
                $nextUrl = $base_url;
            $response = new RedirectResponse(Url::fromUri($nextUrl));
            $response->send();
            return new Response();
        } else {
            refresh_tokens();
            return new Response();
        }
    }

    public function purge(Request $request) {
        $database = \Drupal::database();
        $database->delete('tpedu_units')->execute();
        $database->delete('tpedu_roles')->execute();
        $database->delete('tpedu_classes')->execute();
        $database->delete('tpedu_subjects')->execute();
        $database->delete('tpedu_people')->execute();
        $database->delete('tpedu_jobs')->execute();
        $database->delete('tpedu_assignment')->execute();
        $build = [
            '#markup' => '所有的快取資料都已經移除，快取資料將會在下一次存取時自動取得並保留在資料庫中！',
        ];
        return $build;
    }

    public function notice(Request $request) {
        return array(
            '#theme' => 'tpedu_personal_data_notice',
        );
    }

}