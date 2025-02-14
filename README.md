# What beats rock

<p align="center">
  <img src="https://github.com/user-attachments/assets/bfa8e99b-bbae-4129-ae37-8994cb3fecd9" />
</p>

---

## Installation

Follow these steps to get the application up and running on your local machine:

1. Clone the repository:

```
$ git clone https://github.com/BlackyDrum/what-beats-rock.git
```

2. Navigate to the project directory:

```
$ cd what-beats-rock
```

1. Install the dependencies:

```
$ composer install
```

4. Create a copy of the `.env.example` file and rename it to `.env`. Update the necessary configuration values, such as database credentials and `OPENROUTER_API_KEY`.

5. Generate an application key:

```
$ php artisan key:generate
```

6. Run the database migrations:

```
$ php artisan migrate
```

7. Install JavaScript dependencies:

```
$ npm install
```

8. Build the assets:

```
$ npm run build
```

9.  Start the development server:

```
$ php artisan serve
```

10. Visit `http://localhost:8000` in your web browser to access the application.

## Acknowledgements

-   Original game by [khoi](https://x.com/dragon_khoi) and [kyle](https://x.com/qualiaspace). Check it out [here](https://www.whatbeatsrock.com/).
