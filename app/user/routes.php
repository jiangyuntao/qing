<?php
return array(
    '/users' => 'user,admin/user/index',
    '/users/p/(p:\d+)' => 'user,admin/user/index',
    '/user/(id:\d+)' => 'user,user,show',
);
