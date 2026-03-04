<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentAdminController;
use App\Http\Controllers\ResolverDashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemAdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Resolver routes
Route::middleware(['auth', 'resolver'])->group(function () {
    Route::get('/resolver/dashboard', [ResolverDashboardController::class, 'index'])->name('resolver.dashboard');
    Route::get('/resolver/tickets', [ResolverDashboardController::class, 'getResolverTickets'])->name('resolver.tickets');
    Route::get('/resolver/chart-data', [ResolverDashboardController::class, 'getResolverChartData'])->name('resolver.chart-data');
    Route::get('/resolver/tickets/{ticketId}/group-members', [ResolverDashboardController::class, 'getGroupMembers'])->name('resolver.group-members');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard route with data
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Department registration
    Route::post('auth/department-register', [DashboardController::class, 'registerDepartment'])
        ->name('department.register');

    // Dashboard data endpoints for components
    Route::get('dashboard/data', [DashboardController::class, 'getDashboardDataJson'])
        ->name('dashboard.data');
    
    Route::get('dashboard/tickets', [DashboardController::class, 'getFilteredTickets'])
        ->name('dashboard.tickets');
    
    Route::get('dashboard/chart-data', [DashboardController::class, 'getChartData'])
        ->name('dashboard.chart-data');

    // System Administration Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/system-admin', [SystemAdminController::class, 'index'])->name('system.admin');
        Route::put('/system-admin/users/{user}/status', [SystemAdminController::class, 'updateUserStatus'])->name('system.admin.users.status');
        Route::put('/system-admin/users/{user}/role', [SystemAdminController::class, 'updateUserRole'])->name('system.admin.users.role');
        Route::get('/system-admin/settings', [SystemAdminController::class, 'getSystemSettings'])->name('system.admin.settings');
    });

    // Admin dashboard route
    Route::get('/admin/dashboard', function () {
        return Inertia::render('AdminDashboard');
    })->name('admin.dashboard')->middleware('admin');

    // Department-specific routes
    Route::prefix('department')->group(function () {
        Route::get('dashboard', [DepartmentController::class, 'dashboard'])
            ->name('department.dashboard')
            ->middleware('department.admin');
        
        Route::get('{id}', [DepartmentController::class, 'show'])
            ->name('department.show');
        
        Route::get('{id}/resolvers', [DepartmentController::class, 'resolvers'])
            ->name('department.resolvers');
        
        Route::get('{id}/tickets', [DepartmentController::class, 'tickets'])
            ->name('department.tickets');
    });

    // Department Admin Routes (NEW)
    Route::middleware(['department.admin'])->prefix('dept-admin')->group(function () {
        // Dashboard
        Route::get('dashboard', [DepartmentAdminController::class, 'index'])
            ->name('dept.admin.dashboard');
        
        // Tickets Management
        Route::get('tickets', [DepartmentAdminController::class, 'getTickets'])
            ->name('dept.admin.tickets');
        
        Route::get('my-tickets', [DepartmentAdminController::class, 'getMyTickets'])
            ->name('dept.admin.my-tickets');
        
        // Assignment Operations
        Route::post('tickets/{ticket}/assign', [DepartmentAdminController::class, 'assignTicket'])
            ->name('dept.admin.assign.ticket');
        
        Route::post('tickets/bulk-assign', [DepartmentAdminController::class, 'bulkAssign'])
            ->name('dept.admin.bulk.assign');
        
        Route::put('tickets/order', [DepartmentAdminController::class, 'updateTicketOrder'])
            ->name('dept.admin.tickets.order');
        
        // Resolvers Management
        Route::get('resolvers', [DepartmentAdminController::class, 'getResolvers'])
            ->name('dept.admin.resolvers');
        
        Route::get('resolvers/available', [DepartmentAdminController::class, 'getAvailableResolvers'])
            ->name('dept.admin.resolvers.available');
        
        Route::get('resolvers/{resolverId}', [DepartmentAdminController::class, 'getResolverDetails'])
            ->name('dept.admin.resolver.details');
        
        Route::put('resolvers/{resolverId}/status', [DepartmentAdminController::class, 'updateResolverStatus'])
            ->name('dept.admin.resolver.status');
        
        Route::post('resolvers/bulk-status', [DepartmentAdminController::class, 'bulkUpdateResolverStatus'])
            ->name('dept.admin.resolvers.bulk.status');
        
        // Analytics
        Route::get('chart-data', [DepartmentAdminController::class, 'getChartData'])
            ->name('dept.admin.chart.data');
        
        Route::get('statistics', [DepartmentAdminController::class, 'getStatistics'])
            ->name('dept.admin.statistics');
    });

    // Resolver dashboard
    Route::get('resolver/dashboard', function () {
        return Inertia::render('resolver/Dashboard');
    })->name('resolver.dashboard')->middleware('resolver');
    
    // Ticket routes - UNCHANGED
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets/store', [TicketController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // Ticket assignment routes
    Route::get('/tickets/{ticketId}/resolvers', [TicketController::class, 'getDepartmentResolvers'])
        ->name('tickets.resolvers');

    Route::get('/departments/list', [TicketController::class, 'getAllDepartments'])
        ->name('departments.list');

    Route::post('/tickets/{ticketId}/assign', [TicketController::class, 'assignTicket'])
        ->name('tickets.assign');

    Route::get('/department/resolvers', [TicketController::class, 'getDepartmentResolversList'])
        ->name('department.resolvers.list');

    // Admin-only department management
    Route::middleware(['admin'])->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])
            ->name('departments.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';