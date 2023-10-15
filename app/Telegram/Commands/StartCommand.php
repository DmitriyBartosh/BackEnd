<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Давай начнем наше знакомство!';

    public function handle()
    {
        $userData = $this->getUpdate()->message->from;
        $userId = $userData->id;
        $first_name = $userData->first_name;
        $last_name = $userData->last_name;

        $message = "<b>Привет " . $first_name . " " . $last_name . "!</b>"
            . PHP_EOL .  "Добро пожаловать в Графикси!"
            . PHP_EOL . 'Для начала скопируй этот ID - ' . '<b>' . $userId . '</b>' . " и добавь его в " . "<a href='" . env('FRONTEND_URL') . "/profile/'>Личном кабинете</a>."
            . PHP_EOL . 'Так мы сможем отправлять уведомления от экспертов прямо в телеграм!';

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
}
