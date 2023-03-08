<?php
/***
 * Скрипт заполняет таблицу emails для проверки из данных пользователей, для которых наступило время рассылки
 * Нет смысла проверять почту, если рассылку делать не будем
 */

include_once __DIR__.'/init.php';

pg_query(
    <<<SQL
        INSERT INTO emails(email) 
            (SELECT email FROM users u 
                          WHERE u.email <> '' 
                            AND u.validts > CURRENT_TIMESTAMP - interval '3 day'
                            AND u.email NOT IN (SELECT email FROM emails) 
            )
SQL
);
