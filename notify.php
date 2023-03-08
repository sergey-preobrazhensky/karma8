<?php
/**
 * Скрипт осуществялет отправку уведомлений
 */
include_once __DIR__.'/init.php';

function send_email($from, $to, $subj, $body)
{
    sleep(rand(1, 10));
    return true;
}

/**
 * Возвращает порцию пользователей с учетом возможности параллельной обработки
 *
 * @return array
 */
function get_locked_portion()
{
    pg_query('BEGIN TRANSACTION');
    $res = pg_query(
        <<<SQL
        SELECT * FROM public.users 
        WHERE validts < CURRENT_TIMESTAMP + interval '3 day'
        AND validts > CURRENT_TIMESTAMP 
        AND NOT notified
        AND NOT processing
        AND email IN (SELECT email FROM public.emails WHERE valid)
        LIMIT 10 FOR UPDATE
SQL
    );
    if ($users = pg_fetch_all($res)) {
        $userIds = implode(
            ',',
            array_map(function ($user) {
                return $user['id'];
            }, $users)
        );
        pg_query(
            <<<SQL
            UPDATE public.users SET processing = true WHERE id IN ({$userIds})
SQL
        );
    }
    pg_query('COMMIT TRANSACTION');

    return $users;
}

function notify_portion()
{
    $users = get_locked_portion();
    foreach ($users as $user) {
        send_email(
            'info@company.com',
            $user['email'],
            'Subscription notify',
            $user['username'].', your subscription is expiring soon'
        );
        pg_query(
            <<<SQL
            UPDATE users SET processing = false, notified = true WHERE id = {$user['id']}
SQL
        );
    }
}

notify_portion();
