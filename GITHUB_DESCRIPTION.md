#  Department Admin Dashboard

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![TypeScript](https://img.shields.io/badge/TypeScript-007ACC?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![React](https://img.shields.io/badge/React-20232A?logo=react&logoColor=61DAFB)](https://reactjs.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

A modern, responsive department administration dashboard built with React, TypeScript, and Tailwind CSS. This application provides comprehensive ticket management, resolver management, and analytics for department administrators.

##  Features

- **Advanced Ticket Management** - Drag-and-drop tables, filtering, pagination, and bulk actions
-  **Interactive Analytics** - Real-time statistics and responsive charts
-  **Resolver Management** - Complete user management with performance tracking
-  **Mobile-First Design** - Fully responsive with touch-friendly controls
-  **Modern UI/UX** - Built with ShadCN components and smooth animations
-  **Secure & Robust** - CSRF protection, input validation, and role-based access

##  Tech Stack

**Frontend:**
- React 18 + TypeScript
- Tailwind CSS + ShadCN UI
- TanStack React Table + dnd-kit
- Recharts + Lucide Icons

**Backend:**
- Laravel + Inertia.js
- RESTful APIs + JWT Auth
- MySQL Database

## Quick Start

```bash
# Clone and install
git clone <repository-url>
cd hd-dashboard
npm install
composer install

# Setup environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# Start development
npm run dev
php artisan serve
```

##  Responsive Design

- **Mobile** (< 640px): Stacked layouts, touch controls, horizontal scrolling
- **Tablet** (640px - 1024px): Optimized filters and adaptive tables  
- **Desktop** (> 1024px): Full-featured interface with all controls visible

##  Key Highlights

-  **Drag & Drop Tables** - Intuitive ticket reordering with visual feedback
-  **Advanced Filtering** - Multi-criteria search with real-time updates
-  **Interactive Charts** - Responsive data visualization with time filtering
-  **Mobile Optimized** - Touch-friendly controls and drawer-based details
-  **Modern Components** - Consistent design system with dark mode support

##  Dashboard Overview

- **Statistics Cards**: Real-time metrics for tickets, resolvers, and performance
- **Ticket Management**: Comprehensive table with sorting, filtering, and bulk actions
- **Resolver Administration**: User management with status tracking and performance metrics
- **Analytics Charts**: Interactive area charts showing trends over time

##  Configuration

- **Environment**: Laravel `.env` configuration
- **Styling**: Tailwind CSS customization
- **Components**: Extensible ShadCN component system
- **API**: RESTful endpoints with comprehensive documentation

##  Quality & Performance

- **Type Safety**: Strict TypeScript with comprehensive interfaces
- **Code Quality**: ESLint, Prettier, and PHPStan integration
- **Testing**: Unit tests, E2E tests, and coverage reports
- **Performance**: Lighthouse score 95+, optimized bundles, and lazy loading

##  Documentation

-  **Comprehensive README** with setup and configuration guides
-  **API Documentation** for all endpoints
-  **Component Documentation** with usage examples
-  **Deployment Guide** for production environments


##  Support

-  **Issues**: [Report bugs and request features](https://github.com/your-username/hd-dashboard/issues)
-  **Discussions**: [Community support and questions](https://github.com/your-username/hd-dashboard/discussions)
-  **Email**: support@yourcompany.com

##  Roadmap

- [ ] **Real-time Updates** - WebSocket integration for live data
- [ ] **Advanced Analytics** - Custom reports and export functionality
- [ ] **Mobile App** - React Native companion application
- [ ] **API Rate Limiting** - Enhanced security and performance
- [ ] **Multi-language Support** - Internationalization (i18n)

##  License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Built with  for modern department administration**
