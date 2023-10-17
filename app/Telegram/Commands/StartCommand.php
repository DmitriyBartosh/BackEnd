<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Давай начнем наше знакомство!';

    public function handle()
    {
        $chatData = $this->getUpdate()->message->from;
        $chatId = $chatData->id;
        $first_name = $chatData->first_name;
        $last_name = $chatData->last_name;

        $message = "<b>Привет " . $first_name . " " . $last_name . "!</b>"
            . PHP_EOL .  "Добро пожаловать в Графикси!"
            . PHP_EOL . "Перейдите по  <b><a href='" . env('FRONTEND_URL') . "/telegram?id=" . $chatId . "'>ссылке на Графикси</a></b> и примените полученный ID."
            . PHP_EOL . 'Или введите ID - <b>' . $chatId . "</b> вручную."
            . PHP_EOL . 'Так мы сможем отправлять уведомления от экспертов прямо в телеграм!';

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
}
