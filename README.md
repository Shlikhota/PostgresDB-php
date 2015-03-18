PostgreSQL database abstract layer over PDO extension.
------------------------------------------------------
> **Requires**: PHP >= 5.4

###Install via composer
```SH
composer require shlikhota/postgresdb-php
```

```SQL
-- For run tests
create user psqldriver_owner createdb createuser password '123';
create database psqldriver_tests owner psqldriver_owner;
```

###Initialize and configure (e.g. Laravel)
Create provider app/Providers/DbServiceProvider.php
```PHP
<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DbServiceProvider extends ServiceProvider {

    public function boot() {}

    public function register()
    {
        $this->app->bindShared('db', function($app)
        {
            $db = new \PostgresDB\Driver($app['config']['database.connections.pgsql'], 'Log');
            $debug = (env('APP_ENV') === 'development');
            if ($debug === true) {
                $db->setDebug(true)->isLog(true);
            }
            return $db;
        });
    }

}
```
and edit config/app.php:
```PHP
    'providers' => [
        ...
        // 'Illuminate\Database\DatabaseServiceProvider', // comment default provider
        'App\Providers\PostgresDbServiceProvider',
        ...
    ]
```
and use it
```PHP
# alias
use PostgresDB\Driver as DB;
# user
echo DB::select("'hello from postgres'")->fetchOne();
```

###SELECT
```PHP
// SELECT * FROM users [WHERE active = 1] [LIMIT $limit]
$query = DB::select()->from('users');
if ($limit !== null) {
    $query->limit($limit);
}
if ($active === true) {
    $query->where('active = ?', 1);
}
$result = $query->fetchAll();

// SELECT * FROM o_users WHERE id = 14
DB::select()
    ->from('users')
    ->where('id = ?', 14)
    ->fetchRow();

// SELECT username, password, role_id, email FROM o_users WHERE username LIKE 'test%' ORDER BY id DESC
DB::select('username', 'password', 'role_id', 'email')
    ->from('users')
    ->where('username LIKE ?', 'test%')
    ->order('id', 'desc');

// SELECT username FROM users WHERE id BETWEEN 1 AND 60 LIMIT 10
DB::select('username')
    ->from('users')
    ->where('id BETWEEN', [1, 60])
    ->limit(10);
```

###INSERT
```PHP
// Insert two enrties into group table
DB::insert(
    Groups::table(),
    ['name', 'params'],
    [
        ['Group #1', '{params:[]}'],
        ['Group #2', '{params:[]}']
    ]
);
```

###UPDATE
```PHP
// UPDATE users SET active = 1 WHERE deleted = 0 AND active = 0
DB::update(Users::table(), ['active' => 1], ['deleted' => 0, 'active' => 0]);
```

###DELETE
```PHP
// DELETE FROM users WHERE active = 0
DB::delete(Users::table(), ['active' => 0]);
```

###Additional queries
```PHP
$lately = '1 hour';
$event_type = 1;
$event_more_than = 10;
DB::query('
    WITH rank_up AS (
        SELECT ue.id AS group_id, COUNT(*)
            FROM users_events AS ue
            INNER JOIN users_groups AS ug ON ug.user_id = ue.user_id
            WHERE ue.created_at > now() - interval ? AND
                  ue.event_type_id = ?
            GROUP BY ue.id
            HAVING COUNT(*) > ?
    )
    UPDATE users_groups SET rank = rank + 1 WHERE id IN (SELECT group_id FROM rank_up)
', [$lately, $event_type, $event_more_than]);
```

###Transactions
```PHP
DB::transaction(function($db){
    // This will occur in the transaction
    $user_id = $db->select('id')->from('users')->fetchOne();
    $db->delete('users', ['id' => $user_id]);
});
```
or
```PHP
try {
    DB::begin();
    $user_id = DB::select('id')->from('users')->fetchOne();
    DB::delete('users', ['id' => $user_id]);
    DB::commit();
} catch (Exception $exception) {
    DB::rollback();
}
```
