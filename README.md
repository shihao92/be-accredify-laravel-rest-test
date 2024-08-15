## Steps to start
- php artisan migrate
- php artisan seed
- php artisan serve
- use POST API to login using superadmin@example.com and get the access token
- append the access token on the header with the prefix Bearer when call the protected routes

## API docs
The apidocs are generated via the scribe command using php artisan scribe:generate which is why the function at the controller has a long comments.
php artisan scribe:generate
After the php artisan serve is run, can view the apidocs at http://localhost:8000/docs
Please be mindful that the apidocs is not fully functional yet due to time constraint.
