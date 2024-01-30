<?php

namespace NotificationChannels\Trello;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use NotificationChannels\Trello\Exceptions\CouldNotSendNotification;
use NotificationChannels\Trello\Exceptions\InvalidConfiguration;

class TrelloChannel
{
    /** @var string */
    public const API_ENDPOINT = 'https://api.trello.com/1/cards/';

    /** @var Client */
    protected $client;

    /** @param Client $client */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $notifiable
     * @param Notification $notification
     * @return void
     * @throws CouldNotSendNotification
     * @throws InvalidConfiguration
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!$routing = collect($notifiable->routeNotificationFor('Trello'))) {
            return;
        }

        $key = config('services.trello.key');

        if (is_null($key)) {
            throw InvalidConfiguration::configurationNotSet();
        }

        $trelloParameters = $notification->toTrello($notifiable)->toArray();

        $token = $routing->get('token') ?? config('services.trello.token');
        $idList = $routing->get('idList') ?? config('services.trello.idList');

        $response = $this->client->post(self::API_ENDPOINT . '?key=' . $key . '&token=' . $token, [
            'form_params' => Arr::set($trelloParameters, 'idList', $idList),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }
    }

}
