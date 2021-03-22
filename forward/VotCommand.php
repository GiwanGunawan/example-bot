<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * User "/survey" command
 *
 * Example of the Conversation functionality in form of a simple survey.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class VotCommand extends UserCommand
{
    /**
     * @var string
     */
    // protected $name = 'survey';
    protected $name = 'vot';

    /**
     * @var string
     */
    protected $description = 'Survery for bot users';

    /**
     * @var string
     */
    // protected $usage = '/survey';
    protected $usage = '/vot';

    /**
     * @var string
     */
    // protected $version = '0.4.0';
    protected $version = '1.2.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var Conversation
     */
    protected $conversation;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        // Preparing response
        $data = [
            'chat_id'      => $chat_id,
            // Remove any keyboard by default
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            // Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        // Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        // Load any existing notes from this conversation
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        // Load the current state of the conversation
        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();

        // State machine
        // Every time a step is achieved the state is updated
        switch ($state) {
            case 0:
                // if ($text === '') {
                //     $notes['state'] = 0;
                //     $this->conversation->update();

                //     $data['text'] = 'Type your name:';

                //     $result = Request::sendMessage($data);
                //     break;
                // }

                // $notes['name'] = $text;
                // $text          = '';

                // // No break!

                if ($text === '' || !in_array($text, ['UBIS', 'DATEL', 'WITEL', 'GM'], true)) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(['UBIS', 'DATEL', 'WITEL', 'GM']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Mau ditujukan kepada siapa?';
                    if ($text !== '') {
                        $data['text'] = 'jangan ketik manual, klik keyboardnya ya!';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['kepada'] = $text;
                $text             = '';

                // No break!

            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Tulis masukan disini dibawah sini ya Kak ^_^ ';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['masukan'] = $text;
                // $text             = '';

                // No break!

            case 2:
                $this->conversation->update();
                $out_text = '/vot hasil:' . PHP_EOL;
                unset($notes['state']);
                foreach ($notes as $k => $v) {
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                }

                // $data['photo']   = $notes['photo_id'];
                $data['text'] = $out_text;

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                $data['text'] = 'Input /vot berhasil. Makasi ya Kak! Masukan dari Kaka akan kami pertimbangkan lho!';
                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
