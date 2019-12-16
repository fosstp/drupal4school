<?php

namespace Drupal\tpedu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Oauth2Controller extends ControllerBase {

    public function handle(Request $request) {
        global $base_url;
        $config = \Drupal::config('tpedu.settings');
        if (!($config->get('enable'))) throw new AccessDeniedHttpException();
        $auth_code = $request->query->get('code');
        if ($auth_code) {
            get_tokens($auth_code);
        } else {
            refresh_tokens();
        }
        $uuid = who();
        $user = get_user($uuid);
        $account = \Drupal::database()->select('users')->condition('uuid', $uuid)->execute()->fetchObject();
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
        $response = new RedirectResponse(Url::fromRoute($nextUrl));
        $response->send();
        return new Response();
    }

    public function notice(Request $request) {

    }

}