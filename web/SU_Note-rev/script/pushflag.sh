#! /bin/bash

#flag单独写在某个文件中
echo $1 > /home/xctf/flag 
#或者
echo $1 > /flag

#flag在代码的某个位置
sed -i "s/flag{replace_flag_here}/$1/g" /var/www/html/flag.php

#flag在数据库中
sleep 3
mysql -uroot -proot <<EOF
USE security;
UPDATE flag SET flag ='$1' WHERE flag ='flag{replace_flag_here}';
EOF