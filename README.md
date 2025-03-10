# WP Agency WordPress Plugin

A comprehensive WordPress plugin for managing Agencyn administrative regions (agencies and divisions/cities) with an emphasis on data integrity, user permissions, and performance.

## 🚀 Features

### Core Features
- Full CRUD operations for Agencys and Divisiones/Cities
- Server-side data processing with DataTables integration
- Comprehensive permission system for different user roles
- Intelligent caching system for optimized performance
- Advanced form validation and error handling
- Toast notifications for user feedback

### Dashboard Features
- Interactive statistics display
- Agency and division count tracking
- Real-time updates on data changes

### User Interface
- Modern, responsive design following WordPress admin UI patterns
- Split-panel interface for efficient data management
- Dynamic loading states and error handling
- Custom modal dialogs for data entry
- Toast notifications system

### Data Management
- Automatic code generation for agencies and divisions
- Data validation with comprehensive error checking
- Relationship management between agencies and divisions
- Bulk operations support
- Export capabilities (optional feature)

### Security Features
- Role-based access control (RBAC)
- Nonce verification for all operations
- Input sanitization and validation
- XSS prevention
- SQL injection protection

### Developer Features
- Event-driven architecture for extensibility
- Comprehensive logging system
- Cache management utilities
- Clean, documented code structure

## 📋 Requirements

### WordPress Environment
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Server Requirements
- PHP extensions:
  - PDO PHP Extension
  - JSON PHP Extension

### Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Internet Explorer 11 (basic support)

## 💽 Installation

1. Download the latest release from the repository
2. Upload to `/wp-content/plugins/`
3. Activate the plugin through WordPress admin interface
4. Navigate to 'WP Agency' in the admin menu
5. Configure initial settings under 'Settings' tab

## 🔧 Configuration

### General Settings
- Records per page (5-100)
- Cache management:
  - Enable/disable caching
  - Cache duration (1-24 hours)
- DataTables language (ID/EN)
- Data display format (hierarchical/flat)

### Permission Management
- Granular permission control for:
  - View agency/division lists
  - View details
  - Add new entries
  - Edit existing entries
  - Delete entries
- Custom role creation support
- Default role templates

### Advanced Settings
- Logging configuration
- Export options
- API access (if enabled)

## 🎯 Usage

### Agency Management
1. Navigate to 'WP Agency' menu
2. Use the left panel for agency listing
3. Utilize action buttons for:
   - 👁 View details
   - ✏️ Edit data
   - 🗑️ Delete entries
4. Right panel shows detailed information

### Division Management
1. Select a agency to view its divisions
2. Use the division tab in the right panel
3. Manage divisions with similar actions:
   - Add new divisions
   - Edit existing ones
   - Delete as needed

## 🛠 Development

### Project Structure
```
wp-agency/
├── assets/              # Frontend resources
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── includes/           # Core plugin files
├── src/                # Main source code
│   ├── Cache/          # Caching system
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Data models
│   ├── Validators/     # Input validation
│   └── Views/          # Template files
└── logs/              # Debug logs
```

### Key Components

#### Controllers
- AgencyController: Handles agency CRUD operations
- DivisionController: Manages division operations
- DashboardController: Handles statistics and overview
- SettingsController: Manages plugin configuration

#### Models
- AgencyModel: Agency data management
- DivisionModel: Division data operations
- PermissionModel: Access control
- SettingsModel: Configuration storage

#### JavaScript Components
- Agency management
- Division management
- DataTables integration
- Form validation
- Toast notifications
## 🔌 Plugin Integration

This plugin is designed to be extensible, allowing other plugins to add new functionality through various hooks and filters. Currently supported integrations include:

### Tab Extensions
Other plugins can add new tabs to the company detail panel. This allows for seamless integration of additional functionality while maintaining clean separation of concerns. Features:

- Add custom tabs with any content
- Control tab priority and positioning
- Handle data display and interactions
- Maintain consistent styling

For detailed implementation guide, see [Adding Custom Tabs Documentation](docs/integrasi-tab-company-dari-plugin-lain.md)

Example integration points:
- WordPress filters for registering tabs
- Event system for tab interactions
- Template override capabilities
- Asset management hooks

### Development Guidelines

#### Coding Standards
- Follows WordPress Coding Standards
- PSR-4 autoloading
- Proper sanitization and validation
- Secure AJAX handling

#### Database Operations
- Prepared statements for all queries
- Transaction support for critical operations
- Foreign key constraints
- Indexing for performance

#### JavaScript
- Modular component architecture
- Event-driven communication
- Error handling and validation
- Loading state management

## 🔒 Security

### Authentication & Authorization
- WordPress role integration
- Custom capability management
- Nonce verification
- Permission validation

### Data Protection
- Input sanitization
- Output escaping
- SQL injection prevention
- XSS protection

### Error Handling
- Comprehensive error logging
- User-friendly error messages
- Debug mode support
- Graceful fallbacks

## 📝 Changelog

### Version 1.0.3
- Added extensible tab system in company detail panel
- Implemented WordPress filters for tab registration
- Added events system for tab interactions
- Created documentation for tab extensions
- Added plugin integration guide for custom tabs
- Fixed path inconsistencies in template loading

### Version 1.0.0
- Initial release with core functionality
- Agency and division management
- Permission system implementation
- Caching system
- DataTables integration
- Toast notifications
- Comprehensive documentation

## 🤝 Contributing

1. Fork the repository
2. Create a feature division (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add: AmazingFeature'`)
4. Push to the division (`git push origin feature/AmazingFeature`)
5. Create a Pull Request

## 📄 License

Distributed under the GPL v2 or later License. See `LICENSE` for details.

## 👥 Credits

### Development Team
- Lead Developer: arisciwek

### Dependencies
- jQuery and jQuery Validation
- DataTables library
- WordPress Core

## 📞 Support

For support:
1. Check the documentation
2. Submit issues via GitHub
3. Contact the development team

---

Maintained with ❤️ by arisciwek
