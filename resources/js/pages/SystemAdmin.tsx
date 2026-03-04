// resources/js/Pages/SystemAdmin.tsx
import { AppSidebar } from "@/components/app-sidebar"
import { SiteHeader } from "@/components/site-header"
import { SectionCards } from "@/components/section-cards"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { DataTable } from "@/components/data-table"
import { BarChartComponent } from "@/components/bar-chart-component"
import { PieChartComponent } from "@/components/pie-chart-component"
import {
  SidebarInset,
  SidebarProvider,
} from "@/components/ui/sidebar"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { usePage } from "@inertiajs/react"
import { useState } from "react"

interface SystemStats {
  total_users: number
  active_users: number
  inactive_users: number
  total_tickets: number
  resolved_tickets: number
  open_tickets: number
  total_departments: number
  active_resolvers: number
  average_resolution_time: number; assigned_tickets: number
  overdue_tickets: number
  
}

interface ChartData {
  date: string
  value: number
}

interface UserData {
  id: number
  name: string
  email: string
  is_admin: boolean
  is_resolver: boolean
  is_none: boolean
  is_active: boolean
  department_name?: string
  last_login?: string
  created_at: string
}

interface PageProps {
  auth: {
    user: any
  }
  systemStats: SystemStats
  userRegistrationData: ChartData[]
  ticketResolutionData: ChartData[]
  departmentDistributionData: { name: string; value: number }[]
  users: UserData[]
  user_has_department: boolean
  user_is_admin: boolean
  user_is_resolver: boolean
  user_is_none: boolean

   [key: string]: any
}

export default function SystemAdmin() {
  const { props } = usePage<PageProps>()
  const [activeTab, setActiveTab] = useState("overview")

  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <SiteHeader />
        <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
          <div className="flex items-center justify-between space-y-2">
            <h2 className="text-3xl font-bold tracking-tight">System Administration</h2>
            <div className="flex items-center space-x-2">
              <Button variant="outline" size="sm">
                Refresh Data
              </Button>
            </div>
          </div>

          <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
            <TabsList>
              <TabsTrigger value="overview">System Overview</TabsTrigger>
              <TabsTrigger value="users">User Management</TabsTrigger>
              <TabsTrigger value="analytics">Analytics</TabsTrigger>
              <TabsTrigger value="settings">Settings</TabsTrigger>
            </TabsList>

            {/* System Overview Tab */}
            <TabsContent value="overview" className="space-y-4">
              <SectionCards 
                statistics={props.systemStats} 
                loading={false} 
              />
              
              <div className="grid gap-4 md:grid-cols-2">
                <Card>
                  <CardHeader>
                    <CardTitle>User Registration Trend</CardTitle>
                    <CardDescription>Last 90 days of user registrations</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <ChartAreaInteractive 
                      chartData={props.userRegistrationData} 
                      loading={false}
                      chartType="tickets"
                    />
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Ticket Resolution Flow</CardTitle>
                    <CardDescription>Daily ticket creation vs resolution</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <BarChartComponent 
                      data={props.ticketResolutionData}
                      title="Tickets Daily"
                    />
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* User Management Tab */}
            <TabsContent value="users" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>User Management</CardTitle>
                  <CardDescription>Manage system users and their permissions</CardDescription>
                </CardHeader>
                <CardContent>
                 // Update the users mapping to match Ticket schema:
<DataTable 
  tickets={props.users.map(user => ({
    id: user.id,
    ticket_number: `USER-${user.id}`,
    subject: user.name,
    description: user.email,
    category: user.is_admin ? 'Administrator' : user.is_resolver ? 'Resolver' : 'User',
    priority: user.is_active ? 'active' : 'inactive',
    status: user.is_active ? 'active' : 'inactive',
    assignment_type: 'user' as const,
    assigned_department_id: null,
    assigned_resolver_id: null,
    due_date: null,
    created_at: user.created_at,
    updated_at: user.created_at,
    requester_name: user.name,
    requester_email: user.email,
    assigned_department: user.department_name ? { 
      id: 0, 
      name: user.department_name, 
      slug: user.department_name.toLowerCase().replace(/\s+/g, '-') 
    } : null,
    assigned_resolver: null
  }))} 
  loading={false}
/>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Analytics Tab */}
            <TabsContent value="analytics" className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card className="col-span-2">
                  <CardHeader>
                    <CardTitle>Department Performance</CardTitle>
                    <CardDescription>Ticket resolution by department</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <BarChartComponent 
                      data={props.departmentDistributionData.map(dept => ({
                        date: dept.name,
                        value: dept.value
                      }))}
                      title="Tickets by Department"
                    />
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>User Distribution</CardTitle>
                    <CardDescription>User roles across system</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <PieChartComponent 
                      data={[
                        { name: 'Administrators', value: props.systemStats.active_users - props.systemStats.active_resolvers },
                        { name: 'Resolvers', value: props.systemStats.active_resolvers },
                        { name: 'Regular Users', value: props.systemStats.total_users - props.systemStats.active_users }
                      ]}
                    />
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* Settings Tab */}
            <TabsContent value="settings" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>System Settings</CardTitle>
                  <CardDescription>Configure system preferences and policies</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium">Session Timeout</h4>
                        <p className="text-sm text-muted-foreground">Duration before automatic logout</p>
                      </div>
                      <Button variant="outline">Configure</Button>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium">Password Policy</h4>
                        <p className="text-sm text-muted-foreground">Password complexity requirements</p>
                      </div>
                      <Button variant="outline">Configure</Button>
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium">System Maintenance</h4>
                        <p className="text-sm text-muted-foreground">Put system in maintenance mode</p>
                      </div>
                      <Button variant="outline">Configure</Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </SidebarInset>
    </SidebarProvider>
  )
}