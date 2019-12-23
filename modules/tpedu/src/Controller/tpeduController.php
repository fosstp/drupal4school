<?php

namespace Drupal\tpedu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class tpeduController extends ControllerBase {

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
        $response = new RedirectResponse(Url::fromUri($nextUrl));
        $response->send();
        return new Response();
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
        $response = new RedirectResponse(Url::fromRoute('system.admin_config'));
        $response->send();
        return new Response();
    }

    public function notice(Request $request) {
        return array(
            '#type' => 'markup',
            '#markup' => '<table style="border:0;cellSpacing:0;height:135px;width:700px"><tr>
            <td style="text-align:center;height:50px;vertical-align:center;width:100%">
            <span style="font-family:標楷體;font-size:30px">蒐集個資通告</span>
            </td></tr>
            <tr><td style="text-align:left;width:100%;font-family:標楷體;font-size:16px">
                <p></p>歡迎您使用本校E化服務網站，在你登入網站前，請你務必詳讀以下條款，並同意本條款所有內容以電子文件作為表示方式。一旦你完成登入程序，即視為你已充分了解以下各項條款之約定，並同意遵守以下所有規定。</p>
                <p>謹依個人資料保護法第 8 條規定告知：</p>
                <ul>
                    <li><p><span style="font-weight:bold;">蒐集目的及方式</span><br>
                    本服務網站蒐集個資之目的在於進行身分識別、學生資料管理（079）、資訊與資料庫管理（065）、各項教育行政（053）應用服務之提供。蒐集方式將透過校務行政系統資料庫直接取得或透過互動操作之紀錄進行個人資料之蒐集。</p>
                    </li>
                    <li><p><span style="font-weight:bold;">蒐集之個人資料</span><br>
                    本服務網站所蒐集的個人資料包括：C001－姓名、職稱、電子郵件。C003－身分證字號。C011－性別、出生年月日、國籍。C051－學校紀錄。C066－健康與安全紀錄。網站使用歷程軌跡資料（IP、帳號、進入頁面、進入時間、使用次數、操作動作）。</p>
                    </li>
                    <li><p><span style="font-weight:bold;">利用期間、地區、對象及方式</span><br>
                    期間：教師或志工在校服務期間，學生就學期間（學籍資料依國民教育法第六條第四款之規定永久保存、個案輔導紀錄依檔案法第十二條規定由「行政院研考會檔案管理局」訂定之「機關共通性檔案保存年限基準」應保存二十年、一般輔導紀錄依同法規定應保存三年）。<br>地區：台灣地區。<br>利用對象及方式：資料蒐集僅使用於網站內部管理。</p>
                    </li>
                    <li><p><span style="font-weight:bold;">個資當事人之權利</span><br>
                    <ul>
                        <li>查詢或請求閱覽。</li>
                        <li>請求製給複製本。</li>
                        <li>請求補充或更正。</li>
                        <li>請求停止蒐集、處理或利用。</li>
                        <li>請求刪除。</li>
                    </ul>
                    </li>
                </ul>
                </p><p style="margin-left: 40px;">
                當事人可連絡本校資訊組進行申請，前述之申請，應填具申請文件，並由本人親自申請，本校得向您請求提出可資確認身分之證明文件。若委託他人代為申請者，應出具委任書，並提供本人及代理人之身分證明文件。<span style=font-weight:bold;color:tomato">您若拒絕提供上述個資或是要求刪除或停止蒐集、處理或利用時，除將無法登入本網站繼續使用各項需辨識身分之服務外，也將影響各項教育獎助、社會福利措施之後續通知與申辦。</span></p>
                </td></tr>
            <tr><td style="text-align:center;height:55px"><button onclick="history.go(-1);" style="font-weight:bold;background-color:tomato;color:white">好，我知道了！</button></td></tr>
            </table>',
        );
    }

}