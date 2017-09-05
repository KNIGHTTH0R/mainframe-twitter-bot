web: vendor/bin/heroku-php-apache2 public/
queue: php artisan queue:work redis --sleep=3 --tries=3 --daemon
#supervisor: supervisord -c supervisor.conf -n # update config path relative to Procfile
