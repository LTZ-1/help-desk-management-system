// dashboard.tsx
import { AppSidebar } from "@/components/app-sidebar"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { DataTable } from "@/components/data-table"
import { SectionCards } from "@/components/section-cards"
import { SiteHeader } from "@/components/site-header"
import {
  SidebarInset,
  SidebarProvider,
} from "@/components/ui/sidebar"
import DepartmentSelectDialog from "@/components/DepartmentSelectDialog"
import DepartmentAdminDashboard from "@/components/department-admin-dashboard"
import { useState, useEffect } from "react"
import { router, usePage } from "@inertiajs/react"

interface User {
  id: number
  name: string
  email: string
  department_id?: number
  is_admin: boolean
  is_resolver: boolean
  is_none: boolean
  branch?: string
}

interface Department {
  id: number
  name: string
  slug: string
  description?: string
  is_active?: boolean
}

interface PageProps {
  auth: {
    user: User
  }
  user_has_department: boolean
  user_is_admin: boolean
  user_is_resolver: boolean
  user_is_none: boolean
  user_branch?: string
  departments?: Department[]
  branches?: string[]
  dashboardData?: {
    statistics: any
    chartData: any[]
    recentTickets: any[]
  }
  [key: string]: any
}

// Extend Window interface to include $inertia
declare global {
  interface Window {
    $inertia?: {
      reload: (options?: any) => void
    }
  }
}
export default function Page() {
  const { props } = usePage<PageProps>()
  const [showDepartmentDialog, setShowDepartmentDialog] = useState(false)
  const [dashboardData, setDashboardData] = useState(props.dashboardData || {
    statistics: {
      total_tickets: 0,
      open_tickets: 0,
      assigned_tickets: 0,
      resolved_tickets: 0,
      overdue_tickets: 0,
      active_resolvers: 0
    },
    chartData: [],
    recentTickets: []
  })
  const [loading, setLoading] = useState(!props.dashboardData)
  const [error, setError] = useState<string | null>(null)

  // Fetch dashboard data on component mount if not provided via server
  useEffect(() => {
    if (!props.dashboardData && (props.user_has_department || props.user_is_admin)) {
      fetchDashboardData()
    }
  }, [])

  const fetchDashboardData = async () => {
    try {
      setLoading(true)
      setError(null)
      
      // Use Inertia's router to reload with dashboard data
      router.reload({
        only: ['dashboardData','tilters'],
        onSuccess: (page: any) => {
          setDashboardData(page.props.dashboardData)
        },
        onError: (errors: any) => {
          setError('Failed to load dashboard data')
        }
      })
      
    } catch (error) {
      console.error('Error fetching dashboard data:', error)
      setError('Failed to load dashboard data. Please try again.')
    } finally {
      setLoading(false)
    }
  }
  const getRoleDisplay = () => {
    if (props.user_is_admin && !props.user_has_department) return 'System Administrator'
    if (props.user_is_admin && props.user_has_department) return 'Department Administrator'
    if (props.user_is_resolver) return 'Resolver'
    if (props.user_is_none) return 'User'
    return 'Regular User'
  }

  const getDepartmentName = () => {
    if (props.user_has_department && props.auth.user.department_id) {
      const department = props.departments?.find(d => d.id === props.auth.user.department_id)
      return department?.name || 'Assigned Department'
    }
    return 'Not assigned to department'
  }

  return (
    <SidebarProvider   
      style={{
        "--sidebar-width": "calc(var(--spacing) * 72)",
        "--header-height": "calc(var(--spacing) * 12)",
      } as React.CSSProperties}
    >
      <AppSidebar />
      <SidebarInset>
        <SiteHeader />
        <div className="flex flex-1 flex-col">
          <div className="@container/main flex flex-1 flex-col gap-2">
            <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
              
         
              <div className="px-4 lg:px-6">
                <div className="bg-card rounded-lg border p-6 shadow-sm">
                  <h2 className="text-2xl font-semibold mb-3">Welcome, {props.auth.user.name}!</h2>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted-foreground">
                    <div className="space-y-1">
                      <p className="flex items-center">
                        <span className="font-medium mr-2">Email:</span>
                        {props.auth.user.email}
                      </p>
                      <p className="flex items-center">
                        <span className="font-medium mr-2">Role:</span>
                        <span className={`text-xs font-medium px-2.5 py-0.5 rounded ${
                          props.user_is_admin && !props.user_has_department
                            ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' 
                            : props.user_is_admin && props.user_has_department
                              ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                              : props.user_is_resolver
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                : props.user_is_none
                                  ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                  : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'
                        }`}>
                          {getRoleDisplay()}
                        </span>
                      </p>
                    </div>
                    <div className="space-y-1">
                      <p className="flex items-center">
                        <span className="font-medium mr-2">Department:</span>
                        {getDepartmentName()}
                      </p>
                      <p className="flex items-center">
                        <span className="font-medium mr-2">Branch:</span>
                        {props.user_branch || 'Not specified'}
                      </p>
                    </div>
                  </div>
                      {props.user_is_resolver && (
                    <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                      <p className="text-green-800 text-sm">
                        You are viewing your assigned tickets and resolution statistics.
                      </p>
                    </div>
                  )}
                  {!props.user_has_department && 
                   !props.user_is_admin && 
                   !props.user_is_resolver && 
                   !props.user_is_none && (
                    <div className="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                      <p className="text-amber-800 text-sm">
                        Please complete your registration by selecting your department and role to access all features.
                      </p>
                      <button
                        onClick={() => setShowDepartmentDialog(true)}
                        className="mt-2 bg-amber-600 hover:bg-amber-700 text-white text-xs px-3 py-1 rounded"
                      >
                        Complete Registration
                      </button>
                    </div>
                  )}

                  {props.user_is_none && (
                    <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                      <p className="text-blue-800 text-sm">
                        You are registered as a regular user. You can create and view your tickets.
                      </p>
                    </div>
                  )}
          
                  {props.user_is_resolver && (
                    <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                      <p className="text-green-800 text-sm">
                        You have resolver access to handle assigned tickets.
                      </p>
                    </div>
                  )}

                  {props.user_is_admin && props.user_has_department && (
                    <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                      <p className="text-blue-800 text-sm">
                        You are viewing data for your department: <strong>{getDepartmentName()}</strong>
                      </p>
                    </div>
                  )}

                </div>
              </div>

              {/* Error Message */}
              {error && (
                <div className="px-4 lg:px-6">
                  <div className="bg-destructive/15 text-destructive p-4 rounded-lg border border-destructive/20">
                    <h3 className="font-semibold mb-2">Error Loading Data</h3>
                    <p className="text-sm">{error}</p>
                    <button
                      onClick={fetchDashboardData}
                      className="mt-3 bg-destructive text-destructive-foreground px-3 py-1 rounded text-sm"
                    >
                      Retry
                    </button>
                  </div>
                </div>
              )}

              {/* Dashboard Content */}
              {(props.user_has_department || props.user_is_admin || props.user_is_resolver || props.user_is_none) && !error ? (
                <>
                  {/* Department Admin Dashboard */}
                  {props.user_is_admin && props.user_has_department ? (
                    <DepartmentAdminDashboard />
                  ) : (
                    <>
                      <SectionCards statistics={dashboardData.statistics} loading={loading} error={error || undefined} />
                      <ChartAreaInteractive chartData={dashboardData.chartData} loading={loading} error={error || undefined}
                       chartType={props.user_is_resolver ? 'resolved_tickets' : 'tickets'} />
                      <DataTable tickets={dashboardData.recentTickets} loading={loading} error={error || undefined} />
                    </>
                  )}
                </>
              ) : !error && (
                <div className="px-4 lg:px-6">
                  <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                    <h3 className="text-lg font-medium text-yellow-800 mb-2">
                      Registration Required
                    </h3>
                    <p className="text-yellow-700 mb-4">
                      Please complete your department registration to access the dashboard features.
                    </p>
                    <button
                      onClick={() => setShowDepartmentDialog(true)}
                      className="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md"
                    >
                      Complete Registration
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </SidebarInset>

       <DepartmentSelectDialog 
        open={showDepartmentDialog}
        onOpenChange={setShowDepartmentDialog}
        user={props.auth.user}
        departments={props.departments || []}
        branches={props.branches || []}
      />
    </SidebarProvider>
  )
}