PostgreSQL database abstract layer over PDO extension.
------------------------------------------------------
> **Requires**: PHP >= 5.4

### Install via composer

```SH
composer require shlikhota/postgresdb-php
```

### Initialize and configure

**Use without framework:**

```PHP

use PostgresDB\Driver as DB;
...
echo DB::fetchOne('SELECT ?', 'hello from postgres');
```

**Using Laravel 5.2:**

edit config/app.php:
```PHP
    'providers' => [
        ...
        // Illuminate\Database\DatabaseServiceProvider::class, // comment default provider
        PostgresDB\DbServiceProvider::class,
        ...
    ]
```

and use it

```PHP
use DB;
...
echo DB::fetchOne('SELECT ?', 'hello from postgres');
```

### To run the tests

```SH
createuser -h localhost -e postgresdb_tests
createdb -h localhost -O postgresdb_tests -e postgresdb_tests

phpunit
```

### SELECT

```PHP
/*
SELECT u.username, u.email, r.name
  FROM users AS u
  INNER JOIN roles AS r ON r.id = u.role_id
  WHERE u.username LIKE 'test%'
    AND created_at BETWEEN '2015-06-08' AND '2015-12-30'
    [AND u.active = 1]
  ORDER BY id DESC
  [LIMIT $limit]
*/
$users = DB::select('u.username', 'u.email', 'r.name')
  ->from('users AS u')
  ->innerJoin('roles AS r', 'r.id = u.role_id')
  ->where('u.username LIKE ?', 'test%')
  ->where('created_at BETWEEN ? AND ?', ['2015-06-08', '2015-12-30']);
if ($only_active) {
    $users->where('u.active', 1);
}
if (is_int($limit)) {
    $users->limit($limit);
}
$users->order('id', 'desc');
$result = $users->fetchAll();
```

### Fetching results

```PHP
// $result = [stdClass(id => 5, name => 'Patrick'), stdClass(id => 6, name => 'Edison'), ...]
$result = $users->fetchAll();

// $result = stdClass(id => 5, name => 'Patrick');
$result = $users->fetchRow();

// $result = [['id' => 5, 'name' => 'Patrick'], ['id' => 6, 'name' => 'Edison']]
$result = $users->fetchArray();

// $result = [5, 6];
$result = $users->fetchColumn();

// $result = [5 => ['id' => 5, 'name' => 'Patrick'], 6 => ['id' => 6, 'name' => 'Edison']]
$result = $users->fetchAssoc();

// $result = 5;
$result = $users->fetchOne();

// $result = [5 => 'Patrick', 6 => 'Edison'];
$result = $users->fetchPair();
```

### Stored procedures

```PHP
$email = 'eugene@example.com';
$password = 'secret';
$new_user_id = DB::fetchOne('SELECT register_user(?, ?)', [$email, $password]);
```

### INSERT

```PHP
/*
INSERT INTO groups ('name', 'params') VALUES
  ('Group #1', '{params:[]}'),
  ('Group #2', '{params:[]}');
*/
DB::insert(
    'groups',
    ['name', 'params'],
    [
        ['Group #1', '{params:[]}'],
        ['Group #2', '{params:[]}']
    ]
);
```

### UPDATE

```PHP
// UPDATE users SET active = 1 WHERE deleted = 0 AND active = 0
DB::update('users', ['active' => 1], ['deleted' => 0, 'active' => 0]);
```

### DELETE

```PHP
// DELETE FROM users WHERE active = 0
DB::delete('users', ['create_at BETWEEN ? AND ?' => ['2015-06-08', '2015-12-30']]);
```

### Complex queries

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

### Transactions

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
