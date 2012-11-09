<?php
return array(
    '/users' => 'user,user,index',
    '/users/<p:\d+>' => 'user_p:user,user,index',
    '/user/<id:\d+>' => 'user_show:user,user,show',
);
