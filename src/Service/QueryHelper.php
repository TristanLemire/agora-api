<?php

namespace App\Service;

class QueryHelper
{

    public static function getQueryUserAdditionalData(int $id): string
    {
        return '
            SELECT 
                USER.id AS userId,
                mesure_user.mesureGas,
                mesure_user.mesureWater,
                mesure_user.mesureWaste,
                mesure_user.mesureElectricity,
                user.saving_electricity,
                user.saving_waste,
                user.saving_gas,
                user.saving_water,
                user.saving_transport,
                Period_diff(
                    Date_format(Curdate(), "%y%m"),
                    Date_format(USER.registration_date, "%y%m")
                ) +1 AS nbMonthsRegistered,
                
                (SELECT 
                    Count(task.id)
                FROM task
                    INNER JOIN task_user
                        ON task.id = task_user.task_id
                    INNER JOIN date
                        ON date.id = task.date_id
                    WHERE task_user.user_id = userid
                    AND task.NAME = \'Eau\'
                    AND task.validate = 1
                    AND date.date < Date_format(
                        Now(), \'%Y-%m-01\')
                    ) AS nbValidatedTaskWater,
                    
                (SELECT 
                    Count(task.id)
                FROM task
                   INNER JOIN task_user
                        ON task.id = task_user.task_id
                   INNER JOIN date
                        ON date.id = task.date_id
                   WHERE task_user.user_id = userid
                   AND task.NAME = \'Gaz\'
                   AND task.validate = 1
                   AND date.date < Date_format(
                        Now(), \'%Y-%m-01\')
                   ) AS nbValidatedTaskGas,
                   
                (SELECT 
                    Count(task.id)
                FROM task
                   INNER JOIN task_user
                        ON task.id = task_user.task_id
                   INNER JOIN date
                        ON date.id = task.date_id
                WHERE task_user.user_id = userid
                   AND task.NAME = \'Déchêts\'
                   AND task.validate = 1
                   AND date.date < Date_format(
                        Now(), \'%Y-%m-01\')
                    ) AS nbValidatedTaskWaste,
                    
                (SELECT 
                    Count(task.id)
                FROM task
                   INNER JOIN task_user
                        ON task.id = task_user.task_id
                   INNER JOIN date
                        ON date.id = task.date_id
                WHERE task_user.user_id = userid
                   AND task.NAME = \'Electricté\'
                   AND task.validate = 1
                   AND date.date < Date_format(
                        Now(), \'%Y-%m-01\')
                    ) AS nbValidatedTaskElec,
                    
                (SELECT 
                    Count(task.id)
                FROM task
                   INNER JOIN task_user
                        ON task.id = task_user.task_id
                   INNER JOIN date
                        ON date.id = task.date_id
                WHERE task_user.user_id = userid
                   AND task.NAME = \'Transports\'
                   AND task.validate = 1
                   AND date.date < Date_format(
                        Now(), \'%Y-%m-01\')) AS nbValidateTaskTransport,
                        
                (SELECT 
                    Count(task.id)
                FROM task
                   INNER JOIN task_user
                        ON task.id = task_user.task_id
                   INNER JOIN date
                        ON date.id = task.date_id
                WHERE task_user.user_id = userid
                   AND task.validate = 1
                   AND date.date >= Date_format(Now(), \'%Y-01-01\')
                   AND date.date < Date_format(Now(), \'%Y-%m-01\')) AS nbValidateTaskInThisYear
                        
            FROM USER
            INNER JOIN level
               ON USER.level_id = level.id
            INNER JOIN (
                    SELECT 
                        mesure.to_mesure_id AS mesureUserId,
                        mesure.gas          AS mesureGas,
                        mesure.electricity  AS mesureElectricity,
                        mesure.water        AS mesureWater,
                        mesure.waste        AS mesureWaste
                    FROM mesure
                    INNER JOIN date
                        ON mesure.date_id = date.id
                    WHERE  date.date >= Date_format(Now(), \'%Y-%m-01\')
                ) as mesure_user
               ON USER.id = mesure_user.mesureUserId
            WHERE  USER.id = ' . $id;
    }

    public static function getQueryUserCurrentTasks(int $id): string
    {
        return '
            SELECT 
                task.*
            FROM task
            INNER JOIN task_user
               ON task.id = task_user.task_id
            INNER JOIN USER
               ON task_user.user_id = USER.id
            INNER JOIN date
               ON task.date_id = date.id
            WHERE  date.date >= Date_format(Now(), \'%Y-%m-01\')
            AND USER.id = ' . $id;
    }

    public static function getQueryAllUserTasks(int $id, int $year): string
    {
        return '
            SELECT 
                date.date,
                (mesure.water < USER.water_average_consumption) as waterTaskValidate,
                (mesure.electricity < USER.electricity_average_consumption) as electricityTaskValidate,
                (mesure.gas < USER.gas_average_consumption) as gasTaskValidate,
                (mesure.waste < USER.waste_average_consumption) as wasteTaskValidate,
                ROUND((mesure.water/(USER.water_average_consumption/100))) as waterPercent,
                ROUND((mesure.electricity/(USER.electricity_average_consumption/100))) as electricityPercent,
                ROUND((mesure.gas/(USER.gas_average_consumption/100))) as gasPercent,
                ROUND((mesure.waste/(USER.waste_average_consumption/100))) as wastePercent,
                USER.water_average_consumption,
                USER.electricity_average_consumption,
                USER.gas_average_consumption,
                USER.waste_average_consumption
            FROM   
                mesure
                INNER JOIN 
                    date
                ON 
                    mesure.date_id = date.id
                INNER JOIN 
                    user
                ON 
                    mesure.to_mesure_id = user.id
                Where YEAR(date.date) = ' . $year .
                ' and user.id = ' . $id .
            ' order by date.date DESC';
    }

    public static function getAllUserAndValidateTask(): string
    {
        return '
        SELECT 
            COUNT(user.id) as nbUser , 
            (SELECT 
                COUNT(task.id) 
                FROM task 
                WHERE task.validate is true
            ) as nbValidateTask 
        FROM user';
    }

    public static function getAllStatForALLtaskType(): string
    {
        return '
        SELECT 
            Count(task.id) AS nbValidateTaskByType, 
            task.name, 
            date.date 
        FROM   task 
        INNER JOIN date 
           ON task.date_id = date.id 
        WHERE  task.validate IS TRUE 
        AND date.date < Date_format(Now(), \'%Y-%m-01\') 
        AND date.date >= Date_format(Now(), \'%Y-01-01\') 
        GROUP  BY 
            task.name, 
            date_id 
        ORDER BY date.date';
    }

}
#select date.date, task.* from task inner join task_user on task.id = task_user.task_id inner join user on task_user.user_id = user.id inner join date on task.date_id = date.id where user.id = 1