# Laravel Backend API

## Description
This project is a Laravel backend API.

## Installation

### XAMPP 8.0
1. Download XAMPP 8.0 from [Apache Friends website](https://www.apachefriends.org/index.html).
2. Follow the installation instructions for your operating system.
3. Start the Apache and MySQL services from the XAMPP control panel.

### Composer
1. Download and install Composer from [Composer's official website](https://getcomposer.org/).
2. Follow the installation instructions for your operating system.

### Project Setup
1. Clone this repository to your local machine.
2. Navigate to the project directory.
3. Run composer install to install the project dependencies.
4. Copy the .env.example file and rename it to .env.
5. Generate a new application key by running php artisan key:generate and php artisan jwt:secret
.
6. Configure your .env file with your database connection details.
7. Run php artisan migrate to run the database migrations.
8. (Optional) Run php artisan db:seed to seed the database with dummy data.

## Usage
1. Start your XAMPP server.
2. Navigate to the project directory in your terminal.
3. Run php artisan serve to start the Laravel development server.
4. Access the API endpoints through http://localhost:8000.

## API Documentation
API documentation can be found in the docs directory of this repository.

## Contributing
1. Fork this repository.
2. Create a new branch (git checkout -b feature/your-feature).
3. Commit your changes (git commit -am 'Add some feature').
4. Push to the branch (git push origin feature/your-feature).
5. Create a new Pull Request.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.