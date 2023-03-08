<?php
/**
 * Скрипт проверяет непроверенные email из таблицы emails
 */
include_once __DIR__.'/init.php';

/**
 * Долгая и платная проверка
 *
 * @param $email
 *
 * @return true
 */
function check_email($email): bool
{
    sleep(rand(1, 60));

    return true;
}

/**
 * Быстрая проерка, что значение похоже на email
 * https://habr.com/ru/post/175375/
 *
 * @param $email
 *
 * @return bool
 */
function fast_check_email($email)
{
    return (bool) preg_match("/.+@.+\..+/i", $email);
}

/**
 * Возвращает порцию email с учетом возможности параллельной обработки
 *
 * @return array
 */
function get_locked_portion()
{
    pg_query('BEGIN TRANSACTION');
    $res = pg_query(
        <<<SQL
        SELECT * FROM public.emails
        WHERE NOT checked AND NOT processing
        LIMIT 2 FOR UPDATE
SQL
    );
    if ($email_rows = pg_fetch_all($res)) {
        $email_ids = array_map(function ($email_row) {
            return $email_row['id'];
        }, $email_rows);
        pg_query('UPDATE public.emails SET processing = true 
            WHERE id IN ('.implode(',', $email_ids).')');
    }
    pg_query('COMMIT TRANSACTION');

    return $email_rows;
}

/**
 * Был ли email подтвержден
 *
 * @param $email
 *
 * @return bool
 */
function is_email_confirmed($email)
{
    $res = pg_query(
        <<<SQL
        SELECT 1 FROM public.users
        WHERE email = '{$email}' AND confirmed LIMIT 1
SQL
    );

    return (bool) pg_num_rows($res);
}

function check_portion()
{
    $email_rows = get_locked_portion();
    foreach ($email_rows as $email_row) {
        $email = $email_row['email'];
        /**
         * Сначала быстрая проверка на корректность,
         * потом проверка подтвреждения в базе,
         * только после этого платная долгая проверка
         */
        if (!fast_check_email($email)) {
            $valid = false;
        } elseif (is_email_confirmed($email)) {
            $valid = true;
        } else {
            $valid = check_email($email);
        }
        pg_query(
            <<<SQL
            UPDATE public.emails SET processing = false, checked = true, valid = '{$valid}' 
            WHERE id = {$email_row['id']};
SQL
        );
    }
}

check_portion();
