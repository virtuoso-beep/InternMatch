<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$users = User::all();
foreach ($users as $u) {
    if (Hash::check('password', $u->password)) {
        $pwd = 'password';
    } elseif (Hash::check('Password1234', $u->password)) {
        $pwd = 'Password1234';
    } elseif (Hash::check('internmatch', $u->password)) {
        $pwd = 'internmatch';
    } else {
        $pwd = 'UNKNOWN';
    }
    echo $u->email.' | role='.$u->role->value.' | status='.$u->status->value.' | password='.$pwd.PHP_EOL;
}
