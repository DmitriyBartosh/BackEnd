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
            . PHP_EOL .  "Рады что ты присоединился к сообществу Графикси!"
            . PHP_EOL . 'Скопируй ID - <b>' . $chatId . "</b> и активируй на странице <b><a href='" . env('FRONTEND_URL') . "/telegram/'>Графикси | Телеграм</a></b>."
            . PHP_EOL . 'Так мы сможем отправлять уведомления от экспертов и новые полезные материалы прямо в телеграм!';

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
}
