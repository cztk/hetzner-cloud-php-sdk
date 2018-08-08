<?php

namespace LKDev\HetznerCloud\Models\Actions;

use LKDev\HetznerCloud\HetznerAPIClient;
use LKDev\HetznerCloud\Models\Model;
use LKDev\HetznerCloud\Models\Servers\Server;

/**
 *
 */
class Actions extends Model
{
    /**
     * @var
     */
    public $actions;

    /**
     * @var \LKDev\HetznerCloud\Models\Servers\Server
     */
    public $server;

    /**
     * Actions constructor.
     *
     * @param \LKDev\HetznerCloud\Models\Servers\Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
        parent::__construct();
    }

    /**
     * @return \LKDev\HetznerCloud\Models\Actions\Actions
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function all(): array
    {
        $response = $this->httpClient->get('actions');
        if (!HetznerAPIClient::hasError($response)) {
            $resp = json_decode((string)$response->getBody(), false);
            $resp->server = $this->server;
            return self::parse($resp)->actions;
        }
    }

    /**
     * @param $actionId
     * @return \LKDev\HetznerCloud\Models\Actions\Action
     * @throws \LKDev\HetznerCloud\APIException
     */
    public function get($actionId): Action
    {
        return (new Action($this->httpClient))->getById($actionId);
    }

    /**
     * @param  $input
     * @return $this
     */
    public function setAdditionalData($input)
    {
        $this->actions = collect($input->actions)->map(function ($action, $key) {
            return Action::parse($action);
        })->toArray();

        return $this;
    }

    /**
     * @param $input
     * @param Server $server
     * @return $this|static
     */
    public static function parse($input)
    {
        return (new self($input->server))->setAdditionalData($input);
    }

    /**
     * Wait for an action to complete.
     *
     * @param Action $action
     * @param float $pollingInterval in seconds
     * @return bool
     * @throws \LKDev\HetznerCloud\APIException
     */
    public static function waitActionCompleted(Action $action, $pollingInterval = 0.5)
    {
        while ($action->status == 'running') {
            usleep($pollingInterval * 1000000);
            $action = $action->refresh();
        }
        return $action->status == 'success';
    }
}