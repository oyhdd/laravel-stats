composer require oyhdd/laravel-stats

php artisan vendor:publish --provider="Oyhdd\StatsCenter\StatsCenterServiceProvider"

php artisan stats:install


cd /tmp

wget https://github.com/swoole/swoole-src/archive/v4.4.6.tar.gz

tar zxvf v4.4.6.tar.gz

cd swoole-src-4.4.6/

/usr/bin/phpize

./configure --with-php-config=/www/server/php/72/bin/php-config