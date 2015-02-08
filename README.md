PostgreSQL database abstract layer over PDO extension.
------------------------------------------------------
**Requires**: PHP >= 5.4

```SQL
-- For run tests
create user psqldriver_owner createdb createuser password '123';
create database psqldriver_tests owner psqldriver_owner;
```

Run tests:
```SH
phpunit --stop-on-failure --bootstrap=vendor/autoload.php tests/
```

###SELECT
```PHP
// add alias namespace
use PostgresDB\Driver as DB;
// use instance
$db = DB::instance();

// SELECT * FROM users [WHERE active = 1] [LIMIT $limit]
$query = $db->select()->from('users');
if ($limit !== null) {
    $query->limit($limit);
}
if ($active === true) {
    $query->where('active = ?', 1);
}
$result = $query->fetchAll();

// SELECT * FROM o_users WHERE id = 14
$db->select()
     ->from('users')
     ->where('id = ?', 14)
     ->fetchRow();

// SELECT username, password, role_id, email FROM o_users WHERE username LIKE 'test%' ORDER BY id DESC
$db->select('username', 'password', 'role_id', 'email')
     ->from('users')
     ->where('username LIKE ?', 'test%')
     ->order('id', 'desc');

// SELECT username FROM o_users WHERE id BETWEEN 1 AND 60 LIMIT 10
$db->select('username')
     ->from('users')
     ->where('id BETWEEN', [1, 60])
     ->limit(10);
```

###INSERT
```PHP
// Insert two enrties into group table
$db->insert(
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
// Set active to users whom
$db->update(Users::table(), ['active' => 1], ['deleted' => 0, 'active' => 0]);
```

###DELETE
```PHP
// Remove non-active users
$db->delete(Users::table(), ['active' => 0]);
```
