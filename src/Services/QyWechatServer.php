<?php
namespace Oyhdd\StatsCenter\Services;

use Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

/**
 * 企业微信发送消息
 */
class QyWechatServer
{
    const URL_SEND_MSG  = 'https://qyapi.weixin.qq.com/cgi-bin/message/send';
    const URL_GET_TOKEN = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken';

    private $corp_id; // 企业id
    private $agent_id; // AgentId
    private $corp_secret; // 企业秘钥

    public function __construct($corp_id, $agent_id, $corp_secret)
    {
        $this->corp_id = $corp_id;
        $this->agent_id = $agent_id;
        $this->corp_secret = $corp_secret;
    }

    /**
     * @name   发送微信文本消息（text）
     *
     * @author Eric
     * @param  array       $alarm_uids          用户id
     * @param  string      $content             告警内容
     * @return bool
     */
    public function sendMessage($alarm_uids, $content)
    {
        $userModel = config('admin.database.users_model');
        $qy_wechat_uids = $userModel::whereIn('id', $alarm_uids)->pluck('qy_wechat_uid')->toArray();
        if (empty($qy_wechat_uids)) {
            return false;
        }
        $token = $this->getToken();
        try {
            $url = self::URL_SEND_MSG."?access_token={$token}";
            $data = [
               "touser" => implode('|', $qy_wechat_uids),
               "msgtype" => "text",
               "agentid" => $this->agent_id,
               "text" => [
                    "content" => "【模调系统】".date("Y-m-d H:i:s")."\n".$content,
                ],
               "safe" => 0,
               "enable_id_trans" => 0
            ];

            $client = new Client();
            $response = $client->request('POST', $url, [
                'json' => $data,
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => ' application/json',
                ]
            ])->getBody();
            $response = json_decode($response, true);
            if (!isset($response['errcode'])) {
                return false;
            } elseif ($response['errcode'] == 0) {
                return true;
            } elseif (in_array($response['errcode'], [40014, 41001, 42001])) {
                $this->getToken(true);
            }
        } catch (\Throwable $th) {
            Log::error('发送企业微信消息失败', [sprintf(' %s At %s:%d', $th->getMessage(), $th->getFile(), $th->getLine())]);
        }
        return false;
    }

    /**
     * @name   获取微信token
     *
     * @author Eric
     * @param  bool        $force_refresh   是否强制刷新
     * @return string
     */
    public function getToken($force_refresh = false)
    {
        $token = '';
        try {
            // 从缓存获取
            $key = $this->getKey();
            if (!$force_refresh) {
                $token = Cache::get($key);
            }
            // 重新获取
            if (empty($token)) {
                $url = self::URL_GET_TOKEN."?corpid=".$this->corp_id."&corpsecret=".$this->corp_secret;

                $client = new Client();
                $response = $client->request('GET', $url)->getBody()->getContents();
                $response = json_decode($response, true);
                if (isset($response['errcode']) && $response['errcode'] == 0 && !empty($response['access_token'])) {
                    $token = $response['access_token'];
                    Cache::put($key, $token, $response['expires_in']);
                }
            }
        } catch (\Throwable $th) {
            Log::error('获取企业微信token失败', [sprintf(' %s At %s:%d', $th->getMessage(), $th->getFile(), $th->getLine())]);
        }
        return $token;
    }

    public function getKey($key = 'token')
    {
        return "wechat:{$key}";
    }
}
