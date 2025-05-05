# WP Elabins Nebula

A WordPress plugin for deploying React applications with client-side routing support.

## Description

WP Elabins Nebula allows you to easily deploy React applications within WordPress. It handles:

- React app project creation
- Build file uploads
- Automatic extraction to the correct directory
- WordPress routing support
- Client-side routing compatibility

## Features

- Create new React app "projects" with unique slugs
- Upload and manage React build files
- Automatic extraction to `/wp-content/react-apps/{slug}`
- WordPress routing at `domain.com/{slug}` with client-side routing support
- No `.htaccess` modifications required
- Simple admin interface
- Secure file handling and validation

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-elabins-nebula`
2. Activate the plugin through the WordPress plugins screen
3. Use the Nebula menu item to configure and manage your React apps

## Usage

1. Go to WordPress admin → Nebula
2. Create a new React app project by entering a unique slug
3. Build your React app (`npm run build`)
4. Zip the build output
5. Upload the zip file through the Nebula interface
6. Your app is now available at `domain.com/{your-slug}`

## Development

### Requirements

- PHP 7.4+
- WordPress 5.0+
- Write permissions on `/wp-content/react-apps/`

### Local Development

1. Clone the repository
2. Set up a local WordPress development environment
3. Link or copy the plugin to your local plugins directory
4. Activate the plugin

### Building React Apps

When building React apps for use with this plugin:

1. Ensure your React app uses client-side routing (e.g., React Router)
2. Configure the build to handle nested paths correctly
3. Run `npm run build` to create a production build
4. Zip the contents of the `build` or `dist` directory
5. Upload via the plugin interface

## Security

- Validates all file uploads
- Prevents directory traversal
- Implements WordPress nonces
- Restricts access to authorized users
- Sanitizes all inputs

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
