web: vendor/bin/heroku-php-apache2 public/
supervisor: supervisord -c supervisor.conf -n # update config path relative to Procfile
scheduler: php -d memory_limit=512M artisan schedule:cron
#queue: php artisan queue:work redis --sleep=3 --tries=3 --daemon