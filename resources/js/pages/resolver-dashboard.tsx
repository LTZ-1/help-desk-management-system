/**
 * Resolver Dashboard Component
 * 
 * Provides a comprehensive dashboard for resolver users including:
 * - Personal ticket statistics and metrics
 * - Interactive charts showing ticket trends
 * - "My Tickets" section for assigned tickets management
 * - Data refresh functionality with error handling
 * 
 * Uses Inertia.js for seamless data fetching and state management
 * 
 * @component
 * @author Help Desk Management System
 * @version 1.0
 */

// resolver-dashboard.tsx
"use client"
import * as React from "react"
import { useState, useEffect } from "react"
import { usePage, router } from "@inertiajs/react"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { SectionCards } from "@/components/section-cards"
import { ResolverMyTicketsTab } from "../components/resolver-my-tickets-tab"
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

interface PageProps {
  auth: {
    user: User
  }
  resolverData?: {
    statistics: any
    chartData: any[]
    tickets: any[]
  }
  [key: string]: any
}

export default function ResolverDashboard() {
  const { props } = usePage<PageProps>()
  console.log('ResolverDashboard rendering with props:', props)
  
  const user = props.auth.user
  
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  
  // Share tickets data with My Tickets tab
  const [tickets, setTickets] = useState<any[]>([])
  
  // Calculate statistics from tickets data (same as My Tickets tab)
  const calculateStatistics = (ticketData: any[]) => {
    const stats = {
      total_tickets_to_resolve: ticketData.length,
      assigned_tickets: ticketData.filter((t: any) => t.status === 'assigned').length,
      resolved_tickets: ticketData.filter((t: any) => t.status === 'resolved').length,
      overdue_tickets: ticketData.filter((t: any) => {
        if (!t.due_date) return false
        return new Date(t.due_date) < new Date() && t.status !== 'resolved'
      }).length,
      active_resolvers: 1, // Current resolver
      assigned_resolver_groups: 0,
      // Resolver-specific
      total_tickets_assigned: ticketData.length,
      in_progress_tickets: ticketData.filter((t: any) => t.status === 'in_progress').length,
      group_tickets: ticketData.filter((t: any) => t.assignment_type === 'group').length,
      individual_tickets: ticketData.filter((t: any) => t.assignment_type === 'individual').length
    }
    console.log('Calculated statistics from tickets:', stats)
    return stats
  }
  
  const [statistics, setStatistics] = useState(calculateStatistics([]))
  
  // Calculate chart data from tickets data
  const calculateChartData = (ticketData: any[]) => {
    // Group tickets by date for chart
    const ticketsByDate = ticketData.reduce((acc: any, ticket: any) => {
      const date = new Date(ticket.created_at).toISOString().split('T')[0]
      acc[date] = (acc[date] || 0) + 1
      return acc
    }, {})
    
    const chartData = Object.entries(ticketsByDate).map(([date, count]) => ({
      date,
      tickets: count as number,
      resolved: ticketData.filter((t: any) => 
        new Date(t.created_at).toISOString().split('T')[0] === date && 
        t.status === 'resolved'
      ).length
    })).sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    
    console.log('Calculated chart data from tickets:', chartData)
    return chartData
  }
  
  const [chartData, setChartData] = useState(calculateChartData([]))

  // Update statistics and chart data when tickets change
  useEffect(() => {
    setStatistics(calculateStatistics(tickets))
    setChartData(calculateChartData(tickets))
  }, [tickets])

  // Function to receive tickets from My Tickets tab
  const handleTicketsUpdate = (ticketData: any[]) => {
    console.log('Resolver dashboard received tickets data:', ticketData.length)
    setTickets(ticketData)
    setLoading(false) // Data loaded, stop loading
    setError(null) // Clear any errors
  }

  // Function to handle loading state from My Tickets tab
  const handleLoadingState = (isLoading: boolean) => {
    setLoading(isLoading)
  }

  // Function to handle error state from My Tickets tab
  const handleErrorState = (errorMessage: string) => {
    setError(errorMessage)
    setLoading(false)
  }

  // Refresh function - triggers refetch in My Tickets tab
  const refreshResolverData = () => {
    // This will be handled by the My Tickets tab component
    console.log('Refresh requested - will be handled by My Tickets tab')
  }

  return (
    <div className="space-y-4 p-4 md:p-8 pt-6">
      {/* Error */}
      {error && (
        <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 p-4 rounded-lg">
        <h3 className="font-semibold mb-2">Error Loading Data</h3>
        <p className="text-sm">{error}</p>
        <Button
          variant="outline"
          size="sm"
          onClick={() => refreshResolverData()}
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
            Resolver Dashboard
          </h2>
          <p className="text-gray-600 dark:text-gray-400">
            View and manage your assigned tickets
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => refreshResolverData()}
            disabled={loading}
          >
            Refresh
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <SectionCards statistics={statistics} loading={loading} error={error || undefined} role="resolver" />

      {/* Chart Area - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <ChartAreaInteractive 
            chartData={chartData} 
            loading={loading} 
            error={error || undefined}
            chartType="resolved_tickets"
          />
        </div>
      </div>

      {/* Data Table Tabs - Full width like section cards */}
      <div className="w-full overflow-x-auto">
        <div className="min-w-full">
          <Tabs defaultValue="my-tickets" className="space-y-4">
            <TabsList>
              <TabsTrigger value="my-tickets">My Tickets</TabsTrigger>
            </TabsList>
            
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
                    <ResolverMyTicketsTab 
                    onTicketsUpdate={handleTicketsUpdate}
                    onLoadingState={handleLoadingState}
                    onErrorState={handleErrorState}
                  />
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
