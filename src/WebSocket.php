<?php

/**
 * WebSocket Class
 */
class WebSocket
{
    private $server;

    private $table;

    public $beforeAuthCallback;
    public $afterAuthCallback;
    public $authenticator;
    public $beforeSendMsgCallback;
    public $afterSendMsgCallback;

    private $config = [
        'ws'    => [
            'host' => '0.0.0.0',
            'port' => 9501,
        ]
    ];

    public function __construct($config)
    {
        $this->config = $config;

        $this->init();
    }

    private function init()
    {
        $this->createSwooleTable();

        $this->beforeAuthCallback = function () {
        };

        $this->afterAuthCallback = function () {
        };

        $this->authenticator = function () {
        };

        $this->beforeSendMsgCallback = function () {
        };

        $this->afterSendMsgCallback = function () {
        };
    }

    public function run()
    {
        $this->server = new Swoole\Websocket\Server($this->config['ws']['host'], $this->config['ws']['port']);

        $this->server->set([
            'daemonize' => false,
            // 'log_file'        => '/www/log/swoole.log',
            // 'reactor_num'     => 2,
            // 'worker_num'      => 2,
            // 'task_worker_num' => 4,
            // 'max_request'     => 100,
        ]);

        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);

        $this->server->start();
    }

    public function onOpen(Swoole\Websocket\Server $server, $request)
    {
        call_user_func($this->beforeAuthCallback, $server, $request);

        $userInfo = $this->auth($request);
        if (false === $userInfo) {
            return;
        }

        $user = [
            'fd'     => $request->fd,
            'uid'    => $userInfo['id'],
            'name'   => $userInfo['name'],
            'avatar' => $userInfo['avatar'],
        ];
        $this->table->set($request->fd, $user);

        $this->server->push($request->fd, json_encode([
            'user' => $user,
            'all'  => $this->getAllUser(),
            'type' => 'openSuccess',
        ]));

        $this->pushMessage($server, '欢迎' . $userInfo['name'] . '进入聊天室', 'open', $request->fd);

        call_user_func($this->afterAuthCallback, $server, $request);
    }

    public function onMessage(Swoole\Websocket\Server $server, $frame)
    {
        call_user_func($this->beforeSendMsgCallback, $server, $frame);

        $this->pushMessage($server, $frame->data, 'message', $frame->fd);

        call_user_func($this->afterSendMsgCallback, $server, $frame);
    }

    public function onClose(Swoole\Websocket\Server $server, $fd)
    {
        $user = $this->table->get($fd);
        $this->pushMessage($server, $user['name'] . '离开', 'close', $fd);
        $this->table->del($fd);
    }

    private function pushMessage(Swoole\Websocket\Server $server, $message, $messageType, $frameId)
    {
        $user = $this->table->get($frameId);

        foreach ($this->table as $row) {
            if ($frameId == $row['fd']) {
                continue;
            }

            $server->push($row['fd'], json_encode([
                'type'     => $messageType,
                'message'  => htmlspecialchars($message),
                'datetime' => date('Y-m-d H:i:s'),
                'user'     => $user,
            ]));
        }
    }

    private function getAllUser()
    {
        $users = [];
        foreach ($this->table as $user) {
            $users[] = $user;
        }

        return $users;
    }

    /**
     * auth
     * @auth King
     *
     * @param $request
     *
     * @return bool|array
     */
    private function auth($request)
    {
        $result = call_user_func($this->authenticator, $request);

        if (false === $result) {
            // 关闭连接
            $this->server->push($request->fd, json_encode(['type' => 'error', 'message' => '鉴权失败，请重新登录']));

            return false;
        }

        return $result;
    }

    private function createSwooleTable()
    {
        $this->table = new \Swoole\Table(1024);
        $this->table->column('fd', \Swoole\Table::TYPE_INT);
        $this->table->column('uid', \Swoole\Table::TYPE_INT);
        $this->table->column('name', \Swoole\Table::TYPE_STRING, 255);
        $this->table->column('avatar', \Swoole\Table::TYPE_STRING, 255);

        $this->table->create();
    }
}
