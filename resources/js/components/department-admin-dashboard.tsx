// department-admin-dashboard.tsx
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage } from "@inertiajs/react"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DataTable } from "@/components/data-table"
import { MyTicketsTab } from "@/components/my-tickets-tab"
import { ResolversTab } from "@/components/resolvers-tab"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { SectionCards } from "@/components/section-cards"
import { toast } from "sonner"

interface User {
  id: number
  name: string
  email: string
  department_id: number
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
  [key: string]: any
}

export default function DepartmentAdminDashboard() {
  const { props } = usePage<PageProps>()
  console.log('DepartmentAdminDashboard rendering with props:', props)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [statistics, setStatistics] = useState({
    total_tickets_to_resolve: 0,
    assigned_tickets: 0,
    resolved_tickets: 0,
    overdue_tickets: 0,
    active_resolvers: 0,
    assigned_resolver_groups: 0,
    // Optional fields for compatibility
    total_tickets: 0,
    open_tickets: 0,
    in_progress_tickets: 0,
    group_tickets: 0,
    individual_tickets: 0
  })
  const [chartData, setChartData] = useState([])
  const [tickets, setTickets] = useState([])

  useEffect(() => {
    console.log('DepartmentAdminDashboard useEffect triggered')
    refreshStatistics()
    refreshChartData()
    fetchTickets()
  }, [])

  // Transform backend statistics to frontend format
  const transformStatistics = (backendStats: any) => {
    return {
      total_tickets_to_resolve: backendStats.total_tickets_to_resolve || 0,
      assigned_tickets: backendStats.assigned_tickets || 0,
      resolved_tickets: backendStats.resolved_tickets || 0,
      overdue_tickets: backendStats.overdue_tickets || 0,
      active_resolvers: backendStats.active_resolvers || 0,
      assigned_resolver_groups: backendStats.assigned_resolver_groups || 0,
      // Optional fields for compatibility
      total_tickets: backendStats.total_tickets || backendStats.total_tickets_to_resolve || 0,
      open_tickets: backendStats.open_tickets || backendStats.total_tickets_to_resolve || 0,
      in_progress_tickets: backendStats.in_progress_tickets || 0,
      group_tickets: backendStats.group_tickets || 0,
      individual_tickets: backendStats.individual_tickets || 0
    }
  }

  // Refresh statistics
  const refreshStatistics = async () => {
    try {
      setLoading(true)
      setError(null)
      console.log('Fetching statistics...')
      const response = await fetch('/dept-admin/statistics', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      
      if (!response.ok) {
        throw new Error(`Failed to fetch statistics: ${response.status}`)
      }
      
      const data = await response.json()
      console.log('Statistics received:', data)
      console.log('Transformed statistics:', transformStatistics(data))
      setStatistics(transformStatistics(data))
    } catch (error) {
      console.error('Error fetching statistics:', error)
      setError('Failed to load statistics')
    } finally {
      setLoading(false)
    }
  }

  // Refresh chart data
  const refreshChartData = async (timeRange: string = '90d') => {
    try {
      console.log('Fetching chart data for timeRange:', timeRange)
      const response = await fetch(`/dept-admin/chart-data?timeRange=${timeRange}`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      
      if (!response.ok) {
        throw new Error(`Failed to fetch chart data: ${response.status}`)
      }
      
      const data = await response.json()
      console.log('Chart data received:', data)
      setChartData(data)
    } catch (error) {
      console.error('Error fetching chart data:', error)
      setChartData([])
    }
  }

  // Fetch tickets
  const fetchTickets = async () => {
    try {
      const response = await fetch('/dept-admin/tickets', {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
      
      if (!response.ok) {
        throw new Error('Failed to fetch tickets')
      }
      
      const result = await response.json()
      setTickets(result.tickets.data || [])
    } catch (error) {
      console.error('Error fetching tickets:', error)
      setTickets([])
    }
  }

  return (
    <div className="space-y-4 p-4 md:p-8 pt-6">
      {/* Error Display */}
      {error && (
        <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 p-4 rounded-lg">
          <h3 className="font-semibold mb-2">Error Loading Data</h3>
          <p className="text-sm">{error}</p>
          <Button
            variant="outline"
            size="sm"
            onClick={() => {
              refreshStatistics()
              refreshChartData()
              fetchTickets()
            }}
            className="mt-2"
          >
            Retry
          </Button>
        </div>
      )}

      {/* Header */}
      <div className="flex items-center justify-between space-y-2">
        <div>
          <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Department Admin Dashboard
          </h2>
          <p className="text-gray-600 dark:text-gray-400">
            Manage tickets and resolvers for your department
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => {
              refreshStatistics()
              refreshChartData()
              fetchTickets()
            }}
            disabled={loading}
          >
            Refresh
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <SectionCards statistics={statistics} loading={loading} error={error || undefined} />

      {/* Chart Area - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <ChartAreaInteractive 
            chartData={chartData} 
            loading={loading} 
            error={error || undefined}
            chartType="tickets"
          />
        </div>
      </div>

      {/* Data Table Tabs - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <Tabs defaultValue="tickets" className="space-y-4">
            <TabsList>
              <TabsTrigger value="tickets">Tickets</TabsTrigger>
              <TabsTrigger value="my-tickets">My Tickets</TabsTrigger>
              <TabsTrigger value="resolvers">Resolvers</TabsTrigger>
            </TabsList>
            
            <TabsContent value="tickets" className="space-y-4">
              <Card className="bg-white dark:bg-gray-800">
                <CardHeader>
                  <CardTitle>All Department Tickets</CardTitle>
                  <CardDescription>
                    Manage and assign all tickets in your department
                  </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                  <div className="min-w-full">
                    <DataTable 
                      tickets={tickets} 
                      loading={loading} 
                      error={error || undefined}
                    />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
            
            <TabsContent value="my-tickets" className="space-y-4">
              <Card className="bg-white dark:bg-gray-800">
                <CardHeader>
                  <CardTitle>My Assigned Tickets</CardTitle>
                  <CardDescription>
                    Tickets assigned to you for resolution
                  </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                  <div className="min-w-full">
                    <MyTicketsTab />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
            
            <TabsContent value="resolvers" className="space-y-4">
              <Card className="bg-white dark:bg-gray-800">
                <CardHeader>
                  <CardTitle>Department Resolvers</CardTitle>
                  <CardDescription>
                    Manage resolvers in your department
                  </CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto">
                  <div className="min-w-full">
                    <ResolversTab />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </div>
  )
}
