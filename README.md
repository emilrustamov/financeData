This program is designed for data input and subsequent export via API, in my case to Excel. The program has two access levels: a regular user and an admin. A regular authenticated user cannot edit or delete data, and only authenticated users can access the API.
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan db:seed
