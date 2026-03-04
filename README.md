# Department Admin Dashboard

A modern, responsive department administration dashboard built with React, TypeScript, and Tailwind CSS. This application provides comprehensive ticket management, resolver management, and analytics for department administrators.

## 🚀 Features

### 📊 **Analytics & Statistics**
- Real-time statistics cards showing ticket metrics
- Interactive area charts for ticket trends
- Responsive data visualization
- Time-based filtering (7 days, 30 days, 3 months)

### 🎫 **Ticket Management**
- **Advanced Data Table** with drag-and-drop row reordering
- Multi-column filtering (status, priority, category, search)
- Responsive pagination with customizable page sizes
- Column visibility customization
- Bulk selection and actions
- Mobile-optimized drawer for ticket details

### 👥 **Resolver Management**
- Comprehensive resolver profiles
- Status management (active/inactive/suspended)
- Performance tracking (assigned vs resolved tickets)
- Department-based organization
- Quick action menus

### 📱 **Responsive Design**
- **Mobile-first approach** with touch-friendly controls
- Adaptive layouts for tablet and desktop
- Horizontal scrolling for tables on small screens
- Collapsible navigation and filters
- Optimized breakpoints for all device sizes

### 🎨 **Modern UI/UX**
- Built with **ShadCN UI** components
- Consistent design system
- Dark mode support
- Loading states and error handling
- Accessible and keyboard-navigable
- Smooth animations and transitions

## 🛠️ Technology Stack

### **Frontend**
- **React 18** with TypeScript
- **Tailwind CSS** for styling
- **ShadCN UI** component library
- **TanStack React Table** for data tables
- **dnd-kit** for drag-and-drop functionality
- **Recharts** for data visualization
- **Lucide React** for icons
- **Sonner** for toast notifications

### **Backend Integration**
- **Laravel** MVC architecture with direct database queries
- **Inertia.js** for seamless server-side rendering
- **Eloquent ORM** for database operations
- **Blade templates** with React components
- **Direct controller methods** for data fetching

## 📁 Project Structure

```
resources/js/
├── components/
│   ├── data-table.tsx           # Main ticket management table
│   ├── my-tickets-table.tsx     # User's assigned tickets
│   ├── resolvers-table.tsx       # Resolver management
│   ├── chart-area-interactive.tsx # Interactive charts
│   ├── section-cards.tsx         # Statistics cards
│   └── department-admin-dashboard.tsx # Main dashboard
├── pages/
│   └── dashboard.tsx            # Dashboard page wrapper
└── hooks/
    └── use-mobile.ts             # Mobile detection hook
```

## 🚀 Getting Started

### Prerequisites
- Node.js 18+ 
- PHP 8.1+
- Composer
- Laravel CLI

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd hd-copy
```

2. **Install frontend dependencies**
```bash
npm install
# or
yarn install
```

3. **Install backend dependencies**
```bash
composer install
```

4. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Database setup**
```bash
php artisan migrate
php artisan db:seed
```

6. **Start development servers**
```bash
# Terminal 1 - Backend
php artisan serve

# Terminal 2 - Frontend
npm run dev
```

## 📱 Responsive Breakpoints

- **Mobile**: < 640px (sm)
- **Tablet**: 640px - 1024px (md-lg)
- **Desktop**: > 1024px (xl)

### Mobile Optimizations
- Stacked filter layouts
- Touch-friendly controls
- Horizontal table scrolling
- Bottom drawer for details
- Simplified navigation

## 🎯 Key Features

### **Drag & Drop Ticket Reordering**
- Intuitive row reordering with visual feedback
- Maintains data integrity during reordering
- Undo/redo support
- Mobile touch support

### **Advanced Filtering System**
- Multi-criteria filtering
- Real-time search
- Filter persistence
- Quick filter clearing

### **Responsive Data Tables**
- Sticky headers on scroll
- Horizontal overflow handling
- Adaptive column visibility
- Mobile-optimized pagination

### **Interactive Charts**
- Responsive sizing
- Time range selection
- Hover tooltips
- Animated transitions

## 🔧 Configuration

### **Environment Variables**
```env
# Database
DB_CONNECTION=mysql
DB_DATABASE=hd_dashboard
DB_USERNAME=root
DB_PASSWORD=

# Application
APP_URL=http://localhost:8000
```

### **Customization**
- **Theme**: Modify `tailwind.config.js`
- **Colors**: Update CSS variables in `globals.css`
- **Breakpoints**: Adjust in Tailwind config
- **Components**: Extend ShadCN components

## 📊 Laravel Routes & Data Fetching

### **Dashboard Routes**
- `GET /dashboard` - Main dashboard with server-side data
- `GET /dashboard/data` - JSON data for dashboard components
- `GET /dashboard/tickets` - Filtered tickets data
- `GET /dashboard/chart-data` - Chart data with time filtering

### **Department Admin Routes**
- `GET /dept-admin/dashboard` - Department admin dashboard
- `GET /dept-admin/statistics` - Department statistics from database
- `GET /dept-admin/chart-data` - Chart data with time ranges
- `GET /dept-admin/tickets` - All department tickets
- `GET /dept-admin/my-tickets` - User's assigned tickets
- `POST /dept-admin/tickets/{ticket}/assign` - Assign ticket to resolver
- `PUT /dept-admin/tickets/order` - Update ticket ordering
- `GET /dept-admin/resolvers` - Department resolvers
- `PUT /dept-admin/resolvers/{id}/status` - Update resolver status

### **Ticket Management Routes**
- `GET /tickets/create` - Create ticket form
- `POST /tickets/store` - Store new ticket
- `PUT /tickets/{id}` - Update ticket
- `DELETE /tickets/{id}` - Delete ticket
- `GET /tickets/{id}` - View ticket details
- `POST /tickets/{id}/assign` - Assign ticket to resolver

### **Resolver Routes**
- `GET /resolver/dashboard` - Resolver dashboard
- `GET /resolver/tickets` - Resolver's assigned tickets
- `GET /resolver/chart-data` - Resolver performance data

## 🎨 UI Components

### **Data Tables**
- Sorting and filtering
- Column customization
- Row selection
- Pagination
- Export functionality

### **Forms**
- Validation with Zod
- Real-time feedback
- Accessible labels
- Error handling

### **Charts**
- Area charts for trends
- Responsive containers
- Interactive tooltips
- Time-based filtering

## 🔒 Security Features

- **Laravel Authentication** with built-in session management
- **CSRF Protection** on all forms via Laravel middleware
- **Role-based Middleware** (admin, department.admin, resolver)
- **Input Validation** with Laravel's validation rules
- **Eloquent ORM** for SQL injection prevention
- **Secure Headers** configuration
- **Authorization Gates** for access control

## 🧪 Testing

### **Frontend Tests**
```bash
npm run test          # Unit tests
npm run test:coverage  # Coverage report
```

### **Backend Tests**
```bash
php artisan test        # PHPUnit tests
php artisan test:coverage # Coverage report
```

### **Feature Tests**
- **Dashboard Tests**: Verify dashboard data loading
- **Ticket Routing Tests**: Test ticket assignment logic
- **Authentication Tests**: Verify role-based access
- **Department Tests**: Test department management features

### **E2E Testing**
```bash
npm run test:e2e     # Playwright tests
npm run test:e2e:ui  # Visual testing
```

## 📈 Performance

### **Optimizations**
- **Code Splitting** for faster initial load
- **Lazy Loading** for heavy components
- **Laravel Query Optimization** with eager loading
- **Database Indexing** for faster queries
- **Inertia.js Lazy Props** for conditional data loading
- **Asset Optimization** with Vite
- **Caching Strategy** for database queries

### **Metrics**
- **Lighthouse Score**: 95+
- **First Contentful Paint**: < 1.5s
- **Largest Contentful Paint**: < 2.5s
- **Cumulative Layout Shift**: < 0.1

## 🚀 Deployment

### **Production Build**
```bash
npm run build        # Optimize frontend assets
php artisan optimize     # Optimize Laravel for production
php artisan config:cache  # Cache configuration
php artisan route:cache   # Cache routes
php artisan view:cache    # Cache views
```

### **Docker Deployment**
```bash
docker-compose up -d    # Start containers
docker-compose logs -f  # View logs
```

### **Environment Configuration**
- **Development**: Local development setup
- **Staging**: Pre-production testing
- **Production**: Live environment

## 🤝 Contributing

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### **Code Standards**
- **ESLint** for JavaScript/TypeScript
- **Prettier** for code formatting
- **PHPStan** for PHP code quality
- **Laravel Pint** for PHP code formatting
- **Type Safety** with strict TypeScript
- **PSR Standards** for PHP code

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Laravel** for the amazing PHP framework
- **Inertia.js** for seamless SPA-like experience
- **ShadCN** for beautiful UI components
- **Tailwind CSS** for utility-first CSS framework
- **TanStack** for powerful table library
- **Lucide** for icon set
- **React** team for amazing framework
- **Vite** for fast build tooling

## 📞 Support

For support and questions:
- **Issues**: [GitHub Issues](https://github.com/your-username/hd-dashboard/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/hd-dashboard/discussions)
- **Email**: support@yourcompany.com

---

**Built with ❤️ using modern web technologies**
