# Auto-École Management System

A comprehensive driving school management system built with PHP, MySQL, and TailwindCSS.

## Features

- Multi-user role system (Admin, Instructor, Candidate, Assistant)
- Course management and enrollment
- Scheduling system for driving lessons
- Student progress tracking
- Payment management
- Document management
- Real-time notifications
- Responsive dashboard interfaces

## Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Node.js 14.x or higher
- Composer

## Installation

1. Clone the repository:
```bash
git clone git@github.com:asmaa-dev1/auto-ecole.git
cd auto-ecole
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Create database and configure environment:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

5. Run database migrations:
```bash
php artisan migrate
```

6. Build assets:
```bash
npm run dev
```

## Development

To start the development server:

```bash
npm run dev
```

To build for production:

```bash
npm run build
```

## SSH Key Setup

1. Generate a new SSH key:
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
```

2. Start the SSH agent:
```bash
eval "$(ssh-agent -s)"
```

3. Add your SSH key to the agent:
```bash
ssh-add ~/.ssh/id_ed25519
```

4. Copy the public key:
```bash
cat ~/.ssh/id_ed25519.pub
```

5. Add the key to your GitHub account:
   - Go to GitHub Settings > SSH and GPG keys
   - Click "New SSH key"
   - Title: "auto-ecole"
   - Paste your key and save

## Project Structure

```
|-- assets/         # Static assets (CSS, JS, images)
|-- config/         # Configuration files
|-- includes/       # PHP includes and helpers
|-- pages/         # Application pages
    |-- admin/     # Admin dashboard pages
    |-- auth/      # Authentication pages
    |-- candidate/ # Candidate pages
    |-- instructor/# Instructor pages
|-- index.php      # Application entry point
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.